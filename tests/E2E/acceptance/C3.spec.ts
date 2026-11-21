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

	// AC4-AC7 (C3A; R1-R6, D5): Settings → General → Google Analytics.
	test( 'AC4 the three toggles wait for an ID; clearing it or Click Tracking switches the others off', async ( { page } ) => {
		setSettings( { ga_tracking_id: '', ga_click_tracking: false, ga_pageview: false, ga_anonymize_ip: false } );
		await page.goto( '/wp-admin/admin.php?page=affibloom-settings' );
		const id = page.getByLabel( 'Tracking ID or Property ID', { exact: true } );
		const click = page.getByLabel( 'Click Tracking', { exact: true } );
		const pageview = page.getByLabel( 'Pageview', { exact: true } );
		const anonymize = page.getByLabel( 'IP Anonymization', { exact: true } );
		await expect( page.getByRole( 'heading', { name: 'Google Analytics' } ) ).toBeVisible();
		for ( const toggle of [ click, pageview, anonymize ] ) {
			await expect( toggle ).toBeDisabled();
		}

		await id.fill( 'G-TEST123' );
		await expect( click ).toBeEnabled();
		await click.check();
		await expect( anonymize ).toBeEnabled();
		await anonymize.check();
		await click.uncheck();
		await expect( anonymize ).not.toBeChecked();
		await expect( anonymize ).toBeDisabled();
		await click.check();
		await id.fill( '' );
		await expect( click ).not.toBeChecked();
		await expect( click ).toBeDisabled();
	} );

	test( 'AC5 clicking the disabled Click Tracking marks the ID field and warns', async ( { page } ) => {
		setSettings( { ga_tracking_id: '', ga_click_tracking: false } );
		await page.goto( '/wp-admin/admin.php?page=affibloom-settings' );
		await page.locator( '#affibloom-setting-ga_click_tracking' ).locator( 'xpath=ancestor::*[contains(@class,"affibloom-toggle-row")][1]' ).click( { force: true } );
		await expect( page.getByText( 'Please Enter GA Tracking ID Before Enable Click Tracking' ) ).toBeVisible();
		await expect( page.getByLabel( 'Tracking ID or Property ID', { exact: true } ) ).toHaveClass( /is-invalid/ );
	} );

	test( 'AC6 switching Pageview on asks first; Cancel switches it back off', async ( { page } ) => {
		setSettings( { ga_tracking_id: 'G-TEST123', ga_click_tracking: true, ga_pageview: false } );
		await page.goto( '/wp-admin/admin.php?page=affibloom-settings' );
		const pageview = page.getByLabel( 'Pageview', { exact: true } );
		await pageview.check();
		const ask = page.getByRole( 'dialog', { name: 'Enable Pageview' } );
		await expect( ask.getByRole( 'button', { name: 'Enable' } ) ).toBeVisible();
		await ask.getByRole( 'button', { name: 'Cancel' } ).click();
		await expect( pageview ).not.toBeChecked();
		await pageview.check();
		await ask.getByRole( 'button', { name: 'Enable' } ).click();
		await expect( pageview ).toBeChecked();
	} );

	test( 'AC7 saving keeps the IDs trimmed and the toggles; a reload shows them', async ( { page } ) => {
		setSettings( { ga_tracking_id: '', ga_click_tracking: false, ga_pageview: false, ga_anonymize_ip: false } );
		await page.goto( '/wp-admin/admin.php?page=affibloom-settings' );
		await page.getByLabel( 'Tracking ID or Property ID', { exact: true } ).fill( '  G-ONE111 , UA-2-2  ' );
		await page.getByLabel( 'Click Tracking', { exact: true } ).check();
		await page.getByLabel( 'IP Anonymization', { exact: true } ).check();
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.locator( '.affibloom-toast' ) ).toBeVisible();

		await page.reload();
		await expect( page.getByLabel( 'Tracking ID or Property ID', { exact: true } ) ).toHaveValue( /^G-ONE111 ?, ?UA-2-2$/ );
		await expect( page.getByLabel( 'Click Tracking', { exact: true } ) ).toBeChecked();
		await expect( page.getByLabel( 'IP Anonymization', { exact: true } ) ).toBeChecked();
		setSettings( { ga_tracking_id: '', ga_click_tracking: false, ga_pageview: false, ga_anonymize_ip: false } );
	} );
} );
