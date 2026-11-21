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

	test( 'AC1 box buttons, title, image and product links send Affibloom\'s events', async ( { page } ) => {
		setSettings( { ga_tracking_id: 'G-TEST123', ga_click_tracking: true } );
		const name = unique( 'C3 AC1' );
		const id = createProduct( {
			name,
			url: `https://www.example.com/${ unique( 'c3-1' ) }`,
			button_text: 'Buy Now',
			url2: `https://www.example.com/${ unique( 'c3-2' ) }`,
			button_text2: 'Check Price',
		} );
		await page.goto( newPostWith( `${ boxBlock( id ) }<!-- wp:paragraph --><p><a href="https://www.example.com/c3-link" data-affibloom-id="${ id }">the shoe</a></p><!-- /wp:paragraph -->` ) );
		// The links stay on this page, whose dataLayer the test reads (clicks.js listens first, in the capture phase).
		await page.evaluate( () => document.addEventListener( 'click', ( event ) => event.preventDefault() ) );

		await page.locator( '.affibloom-box .affibloom-button-1' ).click();
		await page.locator( '.affibloom-box .affibloom-button-2' ).click();
		await page.locator( '.affibloom-box a.affibloom-image' ).first().click();
		await page.locator( `p a[data-affibloom-id="${ id }"]` ).click();

		const sent = await events( page );
		expect( sent.map( ( e ) => e[ 1 ] ) ).toEqual( [ 'affibloom_primary_click', 'affibloom_secondary_click', 'affibloom_primary_click', 'affibloom_link_click' ] );
		expect( sent[ 0 ][ 2 ] ).toMatchObject( { event_category: name, event_label: 'Buy Now', affibloom_title: name } );
		expect( sent[ 1 ][ 2 ] ).toMatchObject( { event_label: 'Check Price' } );
		expect( sent[ 2 ][ 2 ] ).toMatchObject( { event_label: 'Buy Now' } ); // the image sends the first button's text
		expect( sent[ 3 ][ 2 ] ).toMatchObject( { event_label: 'the shoe', affibloom_text: 'the shoe' } );
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

	// AC3 (replaced by C6) and AC10 (C3C; R11).
	test( 'AC3 with no ID, Affibloom prints no Google Analytics script', async ( { page } ) => {
		setSettings( { ga_tracking_id: '', ga_click_tracking: true } );
		const id = createProduct( { name: unique( 'C3 AC3' ) } );
		await page.goto( newPostWith( boxBlock( id ) ) );
		await expect( page.locator( '.affibloom-box' ) ).toBeVisible(); // Affibloom is on the page
		// Affibloom's own tag: the site's theme and other plugins print theirs.
		await expect( page.locator( 'script#affibloom-gtag-js, script#affibloom-gtag-js-before' ) ).toHaveCount( 0 );
	} );

	test( 'AC10 with Click Tracking on and IDs, gtag.js loads for the first ID and every ID is configured', async ( { page } ) => {
		setSettings( { ga_tracking_id: 'G-FIRST1, G-SECOND2', ga_click_tracking: true, ga_pageview: false, ga_anonymize_ip: true } );
		await page.goto( newPostWith( '<p>Any page.</p>' ) );
		await expect( page.locator( 'script#affibloom-gtag-js[src*="googletagmanager.com/gtag/js?id=G-FIRST1"]' ) ).toHaveCount( 1 );
		// Affibloom's IDs: the site's own snippet configures its own.
		const configs = await page.evaluate( () =>
			( ( window as unknown as { dataLayer: unknown[] } ).dataLayer ).map( ( entry ) => Array.from( entry as ArrayLike< unknown > ) ).filter( ( entry ) => 'config' === entry[ 0 ] && [ 'G-FIRST1', 'G-SECOND2' ].includes( entry[ 1 ] as string ) )
		);
		expect( configs ).toEqual( [
			[ 'config', 'G-FIRST1', { send_page_view: false, anonymize_ip: true } ],
			[ 'config', 'G-SECOND2', { send_page_view: false, anonymize_ip: true } ],
		] );
		setSettings( { ga_tracking_id: '', ga_click_tracking: false, ga_anonymize_ip: false } );
	} );

	// AC8, AC9 (C3B; R9, R10, D4): Google Tag Manager's dataLayer, then analytics.js.
	test( 'AC8 with no gtag but a dataLayer, the click pushes the event object', async ( { page } ) => {
		setSettings( { ga_tracking_id: 'G-TEST123', ga_click_tracking: true } );
		// gtag blocked (a consent tool, say): a fixed, empty global that no script on the page can define.
		await page.addInitScript( () => {
			delete ( window as unknown as { gtag?: unknown } ).gtag;
			Object.defineProperty( window, 'gtag', { value: undefined, writable: false, configurable: false } );
		} );
		const name = unique( 'C3 AC8' );
		const id = createProduct( { name, url: `https://www.example.com/${ unique( 'c3-8' ) }`, button_text: 'Buy Now' } );
		await page.goto( newPostWith( boxBlock( id ) ) );
		await page.locator( '.affibloom-box .affibloom-button-1' ).click();

		const pushed = await page.evaluate( () => ( ( window as unknown as { dataLayer: Array< Record< string, unknown > > } ).dataLayer ).filter( ( entry ) => 'affibloom_primary_click' === entry.event ) );
		expect( pushed ).toHaveLength( 1 );
		expect( pushed[ 0 ] ).toMatchObject( { event_category: name, event_label: 'Buy Now', affibloom_title: name } );
		setSettings( { ga_tracking_id: '', ga_click_tracking: false } );
	} );

	test( 'AC9 with only analytics.js, the click sends ga( send, event, … )', async ( { page } ) => {
		setSettings( { ga_tracking_id: 'UA-1-1', ga_click_tracking: true } );
		await page.addInitScript( () => {
			const w = window as unknown as { gtag?: unknown; dataLayer?: unknown; ga: ( ...args: unknown[] ) => void; gaCalls: unknown[][] };
			delete w.gtag;
			delete w.dataLayer;
			// Neither gtag nor a dataLayer: fixed, empty globals that no script on the page can define.
			Object.defineProperty( window, 'gtag', { value: undefined, writable: false, configurable: false } );
			Object.defineProperty( window, 'dataLayer', { value: undefined, writable: false, configurable: false } );
			w.gaCalls = [];
			w.ga = ( ...args: unknown[] ) => w.gaCalls.push( args );
		} );
		const name = unique( 'C3 AC9' );
		const href = `https://www.example.com/${ unique( 'c3-9' ) }`;
		const id = createProduct( { name, url: href } );
		await page.goto( newPostWith( boxBlock( id ) ) );
		await page.locator( '.affibloom-box .affibloom-button-1' ).click();

		const calls = await page.evaluate( () => ( window as unknown as { gaCalls: unknown[][] } ).gaCalls );
		expect( calls ).toEqual( [ [ 'send', 'event', name, 'affibloom_primary_click', expect.stringContaining( 'http' ) ] ] );
		setSettings( { ga_tracking_id: '', ga_click_tracking: false } );
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
