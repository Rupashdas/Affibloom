import { test, expect, type Page } from '@playwright/test';
import { blockTrackers, boxBlock, createProduct, newPostWith, setSettings, unique } from '../utils';

// C3 Google Analytics click events (docs/features/C3/requirements.md). In scope since 2026-11-12.
// gtag() pushes its arguments onto window.dataLayer, so the test reads the events from there. That works
// whether gtag is this stub or the site's own snippet (whose gtag.js download is blocked).
type GaEvent = [ string, string, Record< string, unknown > ];

const events = ( page: Page ) =>
	page.evaluate( () =>
		( ( window as unknown as { dataLayer?: unknown[] } ).dataLayer ?? [] )
			.map( ( entry ) => Array.from( entry as ArrayLike< unknown > ) )
			.filter( ( entry ) => entry[ 0 ] === 'event' )
	) as Promise< GaEvent[] >;

test.describe( 'C3 Google Analytics click events', () => {
	test.beforeEach( async ( { context, page } ) => {
		await blockTrackers( context );
		await context.route( /^https:\/\/www\.example\.com\//, ( route ) => route.abort() );
		await page.addInitScript( () => {
			const w = window as unknown as { dataLayer: unknown[]; gtag: ( ...args: unknown[] ) => void };
			w.dataLayer = w.dataLayer || [];
			w.gtag = function () {
				// eslint-disable-next-line prefer-rest-params
				w.dataLayer.push( arguments );
			};
		} );
	} );

	test( 'AC1 box buttons and product links send Lasso\'s events', async ( { page } ) => {
		setSettings( { ga_click_tracking: true } );
		const name = unique( 'C3 AC1' );
		const id = createProduct( {
			name,
			url: `https://www.example.com/${ unique( 'c3-1' ) }`,
			button_text: 'Buy Now',
			url2: `https://www.example.com/${ unique( 'c3-2' ) }`,
			button_text2: 'Check Price',
		} );
		await page.goto( newPostWith( `${ boxBlock( id ) }<!-- wp:paragraph --><p><a href="https://www.example.com/c3-link" data-affibloom-id="${ id }">the shoe</a></p><!-- /wp:paragraph -->` ) );

		await page.locator( '.affibloom-box .affibloom-button-1' ).click();
		await page.locator( '.affibloom-box .affibloom-button-2' ).click();
		await page.locator( `a[data-affibloom-id="${ id }"]:not(.affibloom-button-1):not(.affibloom-button-2)` ).click();

		const sent = await events( page );
		expect( sent.map( ( e ) => e[ 1 ] ) ).toEqual( [ 'Lasso Display Primary Click', 'Lasso Display Secondary Click', 'Lasso Link Click' ] );
		expect( sent[ 0 ][ 2 ] ).toMatchObject( { event_category: name, event_label: 'Buy Now', lasso_title: name } );
	} );

	test( 'AC2 with the setting off, nothing is sent and the click still counts', async ( { page } ) => {
		setSettings( { ga_click_tracking: false } );
		const id = createProduct( { name: unique( 'C3 AC2' ), url: `https://www.example.com/${ unique( 'c3-off' ) }` } );
		await page.goto( newPostWith( boxBlock( id ) ) );

		const counted = page.waitForResponse( ( r ) => r.url().includes( '/wp-json/affibloom/v1/clicks' ) );
		await page.locator( '.affibloom-box .affibloom-button-1' ).click();
		expect( ( await counted ).status() ).toBe( 204 );
		expect( await events( page ) ).toEqual( [] );
		setSettings( { ga_click_tracking: true } ); // the live site's value
	} );

	test( 'AC3 Affibloom adds no Google Analytics script', async ( { page } ) => {
		setSettings( { ga_click_tracking: true } );
		const id = createProduct( { name: unique( 'C3 AC3' ) } );
		await page.goto( newPostWith( boxBlock( id ) ) );
		await expect( page.locator( '.affibloom-box' ) ).toBeVisible(); // Affibloom is on the page
		await expect( page.locator( 'script[id^="affibloom"][src*="googletagmanager"]' ) ).toHaveCount( 0 );
	} );
} );
