import { test, expect } from '@playwright/test';
import { blockTrackers, boxBlock, createGroup, createProduct, newPostWith, unique, wp } from '../utils';

// F12 Click counts and where used (docs/features/F12/requirements.md).
test.describe( 'F12 Clicks and where used', () => {
	// Headless Chromium says "HeadlessChrome", which the bot filter (33B) rightly refuses; the clicks here are a reader's.
	test.use( { userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36' } );

	test.beforeEach( async ( { context } ) => {
		await blockTrackers( context );
		// Box links open the merchant in a new tab; stop that navigation, the click is what is tested.
		await context.route( /^https:\/\/www\.example\.com\//, ( route ) => route.abort() );
	} );

	test( "AC1 a click on a box button raises that product's Clicks", async ( { page } ) => {
		const name = unique( 'F12 AC1' );
		const id = createProduct( { name, url: `https://www.example.com/${ unique( 'f12-ac1' ) }` } );
		await page.goto( newPostWith( boxBlock( id ) ) );

		const counted = page.waitForResponse( ( r ) => r.url().includes( '/wp-json/affibloom/v1/clicks' ) && r.request().method() === 'POST' );
		await page.locator( '.affibloom-box .affibloom-button-1' ).click();
		expect( ( await counted ).status() ).toBe( 204 );

		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await page.getByLabel( 'Search', { exact: true } ).fill( name );
		await page.getByLabel( 'Search', { exact: true } ).press( 'Enter' );
		await expect( page.locator( `tr[data-product-id="${ id }"] td.affibloom-clicks` ) ).toHaveText( '1' );
	} );

	// AC20 (36A; L1-L3, D20, D21): the count is the tied rows plus the group displays that show the product, a link to
	// its Locations tab; a 0 is a clock while the first full index has not finished. The sort part runs in 13K.
	test( "AC20 the Dashboard's Locations column counts tied rows and group displays", async ( { page } ) => {
		const name = unique( 'F12 AC20' );
		const id = createProduct( { name } );
		const slug = createGroup( unique( 'F12 AC20 group' ), [ id ] );
		newPostWith( boxBlock( id ) + `<p>[affibloom type="grid" group="${ slug }"]</p>` ); // saving the post indexes it

		await page.goto( `/wp-admin/admin.php?page=affibloom&post_id=${ id }` );
		const cell = page.locator( `tr[data-product-id="${ id }"] td.affibloom-col-locations` );
		await expect( cell.getByRole( 'link' ) ).toHaveText( '2' );
		await expect( cell.getByRole( 'link' ) ).toHaveAttribute( 'href', new RegExp( `page=affibloom-product&id=${ id }&tab=locations` ) );
	} );

	test( 'AC20 a product used nowhere shows a clock until the first full index has finished', async ( { page } ) => {
		const id = createProduct( { name: unique( 'F12 AC20 clock' ) } );
		const saved = wp( 'option', 'get', 'affibloom_link_index_state', '--format=json' );
		wp( 'option', 'update', 'affibloom_link_index_state', '{"status":"idle","finished_at":""}', '--format=json' );
		try {
			await page.goto( `/wp-admin/admin.php?page=affibloom&post_id=${ id }` );
			const cell = page.locator( `tr[data-product-id="${ id }"] td.affibloom-col-locations` );
			await expect( cell.getByText( 'Processing. Please check back in 24 hours.' ) ).toBeAttached();
		} finally {
			wp( 'option', 'update', 'affibloom_link_index_state', saved, '--format=json' );
		}
		await page.reload();
		await expect( page.locator( `tr[data-product-id="${ id }"] td.affibloom-col-locations` ) ).toHaveText( '0' );
	} );

	// AC4, AC5, AC14, AC16, AC18, AC19 (33A; R5-R8, D1-D4, D9, D10).
	test( 'AC4 every product element counts for its product, middle-click too; a plain link does not', async ( { page, context } ) => {
		const url = `https://www.example.com/${ unique( 'f12-ac4' ) }`;
		const id = createProduct( { name: unique( 'F12 AC4' ), url } );
		const path = newPostWith( boxBlock( id ) + `<p>Read <a href="${ url }">the review</a> or <a href="https://www.example.com/plain">a plain link</a>.</p>` );
		const sent: Array< Record< string, unknown > > = [];
		await context.route( '**/wp-json/affibloom/v1/clicks', async ( route ) => {
			sent.push( JSON.parse( route.request().postData() || '{}' ) );
			await route.fulfill( { status: 204 } );
		} );
		await page.goto( path );

		await page.locator( '.affibloom-box .affibloom-button-1' ).click( { button: 'middle' } );
		await page.locator( '.affibloom-box a.affibloom-image' ).first().click( { modifiers: [ 'Control' ] } );
		await page.getByRole( 'link', { name: 'the review' } ).click( { modifiers: [ 'Control' ] } );
		await page.getByRole( 'link', { name: 'a plain link' } ).click( { modifiers: [ 'Control' ] } );
		await expect.poll( () => sent.length ).toBe( 3 );
		expect( sent.map( ( body ) => body.b ) ).toEqual( [ '1', 'image', 'link' ] );
		expect( sent.every( ( body ) => String( body.p ) === String( id ) ) ).toBe( true );
		expect( Number( sent[ 2 ].l ) ).toBeGreaterThan( 0 );
	} );

	test( 'AC5 a click is stored as a daily total; the route answers 204 and sets no cookie', async ( { request } ) => {
		const id = createProduct( { name: unique( 'F12 AC5' ) } );
		const post = Number( newPostWith( boxBlock( id ) ).replace( '/?p=', '' ) );
		for ( let i = 0; i < 2; i++ ) {
			const answer = await request.post( '/wp-json/affibloom/v1/clicks', { data: JSON.stringify( { p: id, h: post, l: 0, b: '2' } ), headers: { 'Content-Type': 'text/plain' } } );
			expect( answer.status() ).toBe( 204 );
			expect( answer.headers()[ 'set-cookie' ] ).toBeUndefined();
		}
		const stored = wp( 'db', 'query', `SELECT clicks FROM wp_affibloom_clicks WHERE product_id = ${ id } AND post_id = ${ post } AND button = '2'`, '--skip-column-names' );
		expect( Number( stored ) ).toBeGreaterThanOrEqual( 1 );
	} );

	test( 'AC14 trashed and unknown products are not counted; a repointed one counts for its target', async ( { request } ) => {
		const gone = createProduct( { name: unique( 'F12 AC14 gone' ) } );
		wp( 'post', 'delete', String( gone ) );
		for ( const p of [ gone, 999999999, 'x' ] ) {
			const answer = await request.post( '/wp-json/affibloom/v1/clicks', { data: JSON.stringify( { p, h: 0, l: 0, b: '1' } ), headers: { 'Content-Type': 'text/plain' } } );
			expect( answer.status() ).toBe( 204 );
		}
		expect( wp( 'db', 'query', `SELECT COUNT(*) FROM wp_affibloom_clicks WHERE product_id = ${ gone }`, '--skip-column-names' ) ).toBe( '0' );
	} );

	// AC6, AC7 (33B; D5, D6).
	const clickOnce = ( request: import( '@playwright/test' ).APIRequestContext, body: object, agent?: string ) =>
		request.post( '/wp-json/affibloom/v1/clicks', { data: JSON.stringify( body ), headers: { 'Content-Type': 'text/plain', ...( undefined === agent ? {} : { 'User-Agent': agent } ) } } );
	const stored = ( id: number ) => Number( wp( 'db', 'query', `SELECT COALESCE(SUM(clicks), 0) FROM wp_affibloom_clicks WHERE product_id = ${ id }`, '--skip-column-names' ) );

	test( 'AC6 a bot or an empty user agent is not counted', async ( { request } ) => {
		const id = createProduct( { name: unique( 'F12 AC6' ) } );
		for ( const agent of [ 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', '' ] ) {
			expect( ( await clickOnce( request, { p: id, h: 0, l: 0, b: '1' }, agent ) ).status() ).toBe( 204 );
		}
		expect( stored( id ) ).toBe( 0 );
	} );

	test( 'AC7 a repeat within 30 seconds is not counted; after it, it is', async ( { request } ) => {
		const id = createProduct( { name: unique( 'F12 AC7' ) } );
		await clickOnce( request, { p: id, h: 0, l: 0, b: '1' } );
		await clickOnce( request, { p: id, h: 0, l: 0, b: '1' } );
		expect( stored( id ) ).toBe( 1 );
		wp( 'transient', 'delete', '--all' ); // the 30 seconds pass
		await clickOnce( request, { p: id, h: 0, l: 0, b: '1' } );
		expect( stored( id ) ).toBe( 2 );
	} );

	test( 'AC16 with the click route blocked the link still opens and no error shows', async ( { page, context } ) => {
		const id = createProduct( { name: unique( 'F12 AC16' ), url: 'https://www.example.com/ac16' } );
		await context.route( '**/wp-json/affibloom/v1/clicks', ( route ) => route.abort() );
		const errors: string[] = [];
		page.on( 'pageerror', ( error ) => errors.push( error.message ) );
		await page.goto( newPostWith( boxBlock( id ) ) );
		const opened = context.waitForEvent( 'request', ( r ) => r.url().startsWith( 'https://www.example.com/ac16' ) );
		await page.locator( '.affibloom-box .affibloom-button-1' ).click();
		await opened;
		expect( errors ).toEqual( [] );
	} );

	test( 'AC18 a click calls no host but the site; AC19 a post without products has no clicks.js', async ( { page, context } ) => {
		const id = createProduct( { name: unique( 'F12 AC18' ) } );
		const hosts = new Set< string >();
		context.on( 'request', ( r ) => hosts.add( new URL( r.url() ).host ) );
		await page.goto( newPostWith( boxBlock( id ) ) );
		await expect( page.locator( 'script[src*="assets/front/clicks.js"]' ) ).toHaveCount( 1 );
		hosts.clear();
		await page.locator( '.affibloom-box .affibloom-button-1' ).click( { modifiers: [ 'Control' ] } );
		await page.waitForTimeout( 500 );
		expect( [ ...hosts ].filter( ( host ) => /codedrink|lasso|segment|posthog|sentry/.test( host ) ) ).toEqual( [] );

		await page.goto( newPostWith( '<p>No products here.</p>' ) );
		await expect( page.locator( 'script[src*="assets/front/clicks.js"]' ) ).toHaveCount( 0 );
	} );

	test( 'AC2 the Locations tab lists the posts that use the product', async ( { page } ) => {
		const id = createProduct( { name: unique( 'F12 AC2' ) } );
		const title = unique( 'F12 AC2 post' );
		newPostWith( boxBlock( id ), title ); // saving the post indexes it

		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }` );
		await page.locator( '.affibloom-tabs a', { hasText: 'Locations' } ).click();
		await expect( page.getByText( title ) ).toBeVisible();
	} );

	// AC21 (36B; L4-L9, L12, D22, D23).
	test( 'AC21 the Locations tab: columns, grouped rows, sort, search and the empty state', async ( { page } ) => {
		const url = `https://www.example.com/${ unique( 'f12-ac21' ) }`;
		const id = createProduct( { name: unique( 'F12 AC21' ), url } );
		const two = unique( 'F12 AC21 A two' );
		const one = unique( 'F12 AC21 B one' );
		newPostWith( boxBlock( id ) + `<p>Read the <a href="${ url }">review link</a>.</p>`, two );
		newPostWith( boxBlock( id ), one );

		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }&tab=locations` );
		await expect( page.locator( '.affibloom-tabs [aria-current="page"]' ) ).toContainText( 'Locations' );
		await expect( page.locator( '.affibloom-tabs [aria-current="page"]' ) ).toContainText( '3' );
		const head = page.locator( '.affibloom-table--locations thead' );
		await expect( head.getByRole( 'columnheader' ) ).toHaveText( [ /Content/, /Link with Context/, /Type/, /Clicks/ ] );

		// One location is a row; two are a group row "(2 locations)" that expands.
		const group = page.locator( 'tr.affibloom-location-group', { hasText: two } );
		await expect( group ).toContainText( '(2 locations)' );
		await expect( page.locator( 'tr[data-location-of]', { hasText: 'review link' } ) ).toBeHidden();
		await group.getByRole( 'button', { name: 'Toggle locations for this post' } ).click();
		await expect( page.locator( 'tr[data-location-of]', { hasText: 'review link' } ) ).toBeVisible();

		// Content sorts: the first click is ascending.
		await page.getByRole( 'button', { name: 'Content', exact: true } ).click();
		await expect( page.locator( '.affibloom-table--locations tbody tr:not([data-location-of])' ).first() ).toContainText( two );
		await page.getByRole( 'button', { name: 'Content', exact: true } ).click();
		await expect( page.locator( '.affibloom-table--locations tbody tr:not([data-location-of])' ).first() ).toContainText( one );

		// Search finds by post title and by link text.
		await page.getByLabel( 'Search Locations' ).fill( 'review link' );
		await page.getByLabel( 'Search Locations' ).press( 'Enter' );
		await expect( page.locator( '.affibloom-pager__range' ) ).toHaveText( '1-1 of 1' );
		await expect( page ).toHaveURL( /s=review/ );

		// A product used nowhere: Lasso's empty state.
		const none = createProduct( { name: unique( 'F12 AC21 none' ) } );
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ none }&tab=locations` );
		await expect( page.getByText( 'Add This Link Anywhere On Your Website' ) ).toBeVisible();
	} );

	// AC22, AC23 (36C; L10, L11, D24, D25).
	test( 'AC22 the link text opens the preview with the link in its paragraph; a display is drawn', async ( { page } ) => {
		const url = `https://www.example.com/${ unique( 'f12-ac22' ) }`;
		const id = createProduct( { name: unique( 'F12 AC22' ), url } );
		newPostWith( `<p>Before. Our pick is <a href="${ url }">the AC22 ball</a>, then more words.</p><p>Another paragraph.</p>` + boxBlock( id ), unique( 'F12 AC22 post' ) );

		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }&tab=locations` );
		await page.locator( '[data-location-toggle]' ).click();
		await page.getByRole( 'button', { name: 'the AC22 ball' } ).click();
		const dialog = page.getByRole( 'dialog', { name: 'Link with Context' } );
		await expect( dialog ).toContainText( 'Our pick is the AC22 ball, then more words.' );
		await expect( dialog ).not.toContainText( 'Another paragraph.' );
		await expect( dialog.getByRole( 'link', { name: 'the AC22 ball' } ) ).toHaveAttribute( 'target', '_blank' );
		await dialog.getByRole( 'button', { name: 'Close' } ).click();

		await page.getByRole( 'button', { name: 'This is a single link display.' } ).click();
		await expect( dialog.frameLocator( 'iframe' ).locator( '.affibloom-box' ) ).toBeVisible();
	} );

	test( 'AC23 the Clicks column shows 0 for a location without clicks', async ( { page } ) => {
		const id = createProduct( { name: unique( 'F12 AC23' ) } );
		newPostWith( boxBlock( id ) );

		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }&tab=locations` );
		await expect( page.locator( '.affibloom-table--locations tbody td.affibloom-col-clicks' ).first() ).toHaveText( '0' );
	} );

	// AC24 (36D; L13): Placements narrows the list with the count's rule, counts as a filter and is kept in saved views.
	test( 'AC24 the Placements filter: in content, not in content, kept in a saved view', async ( { page } ) => {
		const prefix = unique( 'F12 AC24' );
		const used = createProduct( { name: `${ prefix } used` } );
		const grouped = createProduct( { name: `${ prefix } grouped` } );
		const unused = createProduct( { name: `${ prefix } unused` } );
		const slug = createGroup( unique( 'F12 AC24 group' ), [ grouped ] );
		newPostWith( boxBlock( used ) + `<p>[affibloom type="grid" group="${ slug }"]</p>` );

		await page.goto( `/wp-admin/admin.php?page=affibloom&s=${ encodeURIComponent( prefix ) }` );
		await page.getByRole( 'button', { name: 'Filters' } ).click();
		const rows = page.locator( '.affibloom-table tbody tr[data-product-id]' );

		await page.getByLabel( 'Placements' ).selectOption( 'has_placements' );
		await expect( rows ).toHaveCount( 2 );
		await expect( page.locator( `tr[data-product-id="${ unused }"]` ) ).toHaveCount( 0 );
		await expect( page ).toHaveURL( /placements=has_placements/ );
		await expect( page.locator( '.affibloom-filters__count' ) ).toHaveText( '1' );

		await page.getByLabel( 'Placements' ).selectOption( 'no_placements' );
		await expect( rows ).toHaveCount( 1 );
		await expect( page.locator( `tr[data-product-id="${ unused }"]` ) ).toHaveCount( 1 );

		const name = unique( 'AC24 view' );
		await page.getByLabel( 'Save as' ).fill( name );
		await page.getByLabel( 'Save as' ).press( 'Enter' );
		await expect( page.locator( '.affibloom-pill', { hasText: name } ) ).toHaveAttribute( 'data-view-placements', 'no_placements' );
		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await page.locator( '.affibloom-pill', { hasText: name } ).click();
		await expect( page ).toHaveURL( /placements=no_placements/ );
		await expect( rows ).toHaveCount( 1 );
	} );

	// AC26 (36B; decision 6): unpublished hosts are listed with their status and count as use.
	test( 'AC26 a draft that uses the product is listed with its status', async ( { page } ) => {
		const id = createProduct( { name: unique( 'F12 AC26' ) } );
		const title = unique( 'F12 AC26 draft' );
		wp( 'post', 'create', '--post_type=post', '--post_status=draft', `--post_title=${ title }`, `--post_content=${ boxBlock( id ) }` );

		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }&tab=locations` );
		const row = page.locator( 'tbody tr', { hasText: title } );
		await expect( row.locator( '.affibloom-location__status' ) ).toHaveText( 'Draft' );
	} );

	// AC3 (path corrected 2026-11-14) and AC25 (36E; L14-L16, D26): Lasso asks the count before any dialog.
	test( 'AC3 a product in use cannot be deleted: Hold Up lists its posts and offers only Keep This Link', async ( { page } ) => {
		const id = createProduct( { name: unique( 'F12 AC3' ) } );
		const title = unique( 'F12 AC3 post' );
		newPostWith( boxBlock( id ), title );

		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }` );
		await page.getByRole( 'button', { name: 'Delete This Link' } ).click();
		const holdUp = page.getByRole( 'dialog', { name: 'Hold Up' } );
		await expect( holdUp ).toContainText( title );
		await expect( holdUp.locator( '.affibloom-modal__foot' ).getByRole( 'button' ) ).toHaveText( [ 'Keep This Link' ] );
		await holdUp.getByRole( 'button', { name: 'Keep This Link' } ).click();
		await expect( holdUp ).toBeHidden();
		await expect( page.getByRole( 'dialog', { name: 'Delete This Link?' } ) ).toBeHidden();
		expect( wp( 'post', 'get', String( id ), '--field=post_status' ) ).toBe( 'publish' );
	} );

	test( 'AC25 in use: the row cannot be ticked and the routes answer 409', async ( { page } ) => {
		const id = createProduct( { name: unique( 'F12 AC25' ) } );
		newPostWith( boxBlock( id ) );

		await page.goto( `/wp-admin/admin.php?page=affibloom&post_id=${ id }` );
		const box = page.locator( `tr[data-product-id="${ id }"] .affibloom-check` );
		await expect( box ).toBeDisabled();
		await expect( box ).toHaveAttribute( 'title', "You can't delete a URL if it has active links or displays" );

		const nonce = await page.evaluate( () => ( window as unknown as { wpApiSettings: { nonce: string } } ).wpApiSettings.nonce );
		for ( const path of [ `products/${ id }`, `products?ids[]=${ id }` ] ) {
			const answer = await page.request.delete( `/wp-json/affibloom/v1/${ path }`, { headers: { 'X-WP-Nonce': nonce } } );
			expect( answer.status() ).toBe( 409 );
			expect( ( await answer.json() ).code ).toBe( 'affibloom_in_use' );
		}
		expect( wp( 'post', 'get', String( id ), '--field=post_status' ) ).toBe( 'publish' );
	} );
} );
