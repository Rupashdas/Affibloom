import { test, expect } from '@playwright/test';
import { blockTrackers, createProduct, newPostWith, setSettings, unique, wp } from '../utils';

// F22 Opportunities (docs/features/F22/requirements.md).
test.describe( 'F22 Opportunities', () => {
	test.beforeEach( async ( { context } ) => {
		await blockTrackers( context );
	} );

	/** A product on its own domain and a post with untied links to that domain: one opportunity per link. */
	const fixture = ( links: number ) => {
		const domain = `${ unique( 'shop' ) }.example`;
		const product = createProduct( { name: unique( 'F22 product' ), url: `https://${ domain }/ref?tag=a` } );
		const title = unique( 'F22 post' );
		const body = Array.from( { length: links }, ( _, i ) => `<p>See <a href="https://www.${ domain }/item-${ i }">item ${ i } luminous</a>.</p>` ).join( '' );
		newPostWith( body, title );
		return { domain, product, title };
	};

	// AC1 (O1; R1-R16, D1-D8).
	test( 'AC1 Link Opportunities: the tab, the count, ten a page, search, sort, the intro box', async ( { page } ) => {
		const { domain, product } = fixture( 12 );
		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await page.locator( '.affibloom-header' ).getByRole( 'link', { name: 'Opportunities' } ).click();
		await expect( page ).toHaveURL( /page=affibloom-opportunities/ );
		await expect( page.getByRole( 'heading', { name: 'Link Opportunities' } ) ).toBeVisible();
		await expect( page.locator( '.affibloom-tabs' ) ).toContainText( 'Links' );

		await page.goto( `/wp-admin/admin.php?page=affibloom-opportunities&product=${ product }` );
		await expect( page.locator( '[data-found]' ) ).toHaveText( '12 Found' );
		await expect( page.locator( '.affibloom-table--opportunities tbody tr' ) ).toHaveCount( 10 );
		await expect( page.locator( '.affibloom-table--opportunities thead' ).getByRole( 'columnheader' ) ).toHaveText( [ /URL/, /Content/, /Suggestion/, /Type/, /Monetize/ ] );

		await page.getByLabel( 'Search Opportunities' ).fill( `${ domain } item-1` );
		await page.getByLabel( 'Search Opportunities' ).press( 'Enter' );
		await expect( page.locator( '[data-found]' ) ).toHaveText( '3 Found' ); // item-1, item-10, item-11
		await page.getByRole( 'button', { name: 'URL', exact: true } ).click();
		await page.getByRole( 'button', { name: 'URL', exact: true } ).click();
		await expect( page.locator( '.affibloom-table--opportunities tbody tr' ).first() ).toContainText( 'item-11' );

		await page.getByLabel( 'Search Opportunities' ).fill( 'nothing-like-this' );
		await page.getByLabel( 'Search Opportunities' ).press( 'Enter' );
		await expect( page.getByText( 'No opportunities found.' ) ).toBeVisible();

		const intro = page.locator( '[data-intro="links"]' );
		if ( await intro.count() ) {
			await intro.getByRole( 'button', { name: 'Close this box for good' } ).click();
			await page.reload();
			await expect( page.locator( '[data-intro="links"]' ) ).toHaveCount( 0 );
			wp( 'eval', "$s = get_option( 'affibloom_settings', array() ); $s['education_hidden'] = array(); update_option( 'affibloom_settings', $s );" );
		}
	} );

	// AC2 (O1; R8-R11, R13).
	test( 'AC2 a row: the links open in new tabs, Filter by Link narrows to the product, the Type icon previews', async ( { page } ) => {
		const { product, title } = fixture( 1 );
		await page.goto( `/wp-admin/admin.php?page=affibloom-opportunities&product=${ product }` );
		const row = page.locator( '.affibloom-table--opportunities tbody tr' ).first();
		await expect( row.getByRole( 'link', { name: title } ) ).toHaveAttribute( 'target', '_blank' );
		await expect( row.getByRole( 'link', { name: /item-0/ } ).first() ).toHaveAttribute( 'target', '_blank' );
		await expect( page.getByRole( 'heading', { name: /^Link › / } ) ).toBeVisible();

		await row.getByRole( 'button', { name: 'This is a text link.' } ).click();
		await expect( page.getByRole( 'dialog', { name: 'Link with Context' } ) ).toContainText( 'See item 0 luminous.' );
	} );

	// AC3 (O2; R17-R23).
	test( 'AC3 Domains: counts, biggest first, the lightbulb, and Detected Links with its search', async ( { page } ) => {
		const { domain, title } = fixture( 3 );
		const stray = `${ unique( 'stray' ) }.example`;
		newPostWith( `<p><a href="https://${ stray }/a">a stray link</a></p>` );

		await page.goto( `/wp-admin/admin.php?page=affibloom-opportunities&tab=domains&s=${ encodeURIComponent( domain ) }` );
		const row = page.locator( '.affibloom-table--domains tbody tr', { hasText: domain } );
		await expect( row.locator( 'td.affibloom-col-links' ) ).toHaveText( '3' );
		await expect( row.locator( '.affibloom-alert--opportunity' ) ).toHaveCount( 1 );
		await page.goto( `/wp-admin/admin.php?page=affibloom-opportunities&tab=domains&s=${ encodeURIComponent( stray ) }` );
		await expect( page.locator( '.affibloom-table--domains tbody tr', { hasText: stray } ).locator( '.affibloom-alert--opportunity' ) ).toHaveCount( 0 );

		await page.goto( `/wp-admin/admin.php?page=affibloom-opportunities&tab=domains&s=${ encodeURIComponent( domain ) }` );
		await row.getByRole( 'link', { name: domain } ).click();
		await expect( page.getByRole( 'heading', { name: `Detected Links › ${ domain }` } ) ).toBeVisible();
		await expect( page.locator( '[data-found]' ) ).toHaveText( '3 Found' );
		await page.getByLabel( 'Search Opportunities' ).fill( 'item 2' );
		await page.getByLabel( 'Search Opportunities' ).press( 'Enter' );
		await expect( page.locator( '[data-found]' ) ).toHaveText( '1 Found' );
		await expect( page.locator( 'tbody tr' ).first() ).toContainText( title );
	} );

	// AC4 (O3; R24-R28, R32, R33).
	test( 'AC4 Content: posts with indexed links, their counts, and the filter bar', async ( { page } ) => {
		const { title } = fixture( 2 );
		await page.goto( `/wp-admin/admin.php?page=affibloom-opportunities&tab=content&s=${ encodeURIComponent( title ) }` );
		const row = page.locator( '.affibloom-table--content tbody tr', { hasText: title } );
		await expect( row.locator( 'td.affibloom-col-links' ) ).toHaveText( '2' );

		await page.getByLabel( 'Show' ).selectOption( 'unmonetized' );
		await expect( row ).toHaveCount( 1 ); // both links are untied
		await expect( page ).toHaveURL( /show=unmonetized/ );
		await page.getByLabel( 'Post type' ).selectOption( 'page' );
		await expect( page.getByText( "Looks like we're coming up empty." ) ).toBeVisible();
		await page.getByLabel( 'Post type' ).selectOption( 'post' );
		await page.getByLabel( 'Min link clicks' ).fill( '1' );
		await page.getByLabel( 'Min link clicks' ).press( 'Enter' );
		await expect( page.getByText( "Looks like we're coming up empty." ) ).toBeVisible(); // no product, no clicks
	} );

	// AC5, AC6 (O5; R34-R39, D9).
	test( "AC5 the product's Opportunities tab lists its opportunities; the badge is the list's total", async ( { page } ) => {
		const { product } = fixture( 3 );
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ product }` );
		await expect( page.locator( '[data-tab-count="opportunities"]' ) ).toHaveText( '3' );
		await page.locator( '.affibloom-tabs a', { hasText: 'Opportunities' } ).click();
		await expect( page ).toHaveURL( /tab=opportunities/ );
		await expect( page.locator( '.affibloom-table--opportunities tbody tr' ) ).toHaveCount( 3 );
		await expect( page.getByText( 'Filter by Link' ) ).toHaveCount( 0 );
		await page.getByLabel( 'Search Opportunities' ).fill( 'item-2' );
		await page.getByLabel( 'Search Opportunities' ).press( 'Enter' );
		await expect( page.locator( '[data-found]' ) ).toHaveText( '1 Found' );
	} );

	test( 'AC6 Detect Opportunities off: the product leaves every list; a new product takes the setting', async ( { page } ) => {
		const { product } = fixture( 2 );
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ product }` );
		await page.getByLabel( 'Detect Opportunities' ).uncheck();
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.locator( '[data-tab-count="opportunities"]' ) ).toHaveText( '0' );
		await page.goto( `/wp-admin/admin.php?page=affibloom-opportunities&product=${ product }` );
		await expect( page.locator( '[data-found]' ) ).toHaveText( '0 Found' );

		setSettings( { detect_opportunities: false } );
		const fresh = createProduct( { name: unique( 'F22 AC6 new' ) } );
		expect( wp( 'post', 'meta', 'get', String( fresh ), '_affibloom_detect_opportunities' ) ).toBe( '0' );
		setSettings( { detect_opportunities: true } );
	} );

	// AC7 (O6; R41-R43).
	test( 'AC7 Link Defaults: Only New Links changes nothing; Override All Links turns it off on every product', async ( { page } ) => {
		const first = createProduct( { name: unique( 'F22 AC7a' ) } );
		const second = createProduct( { name: unique( 'F22 AC7b' ) } );
		await page.goto( '/wp-admin/admin.php?page=affibloom-settings&tab=general' );
		const toggle = page.getByLabel( 'Detect Opportunities', { exact: true } );
		const dialog = page.getByRole( 'dialog', { name: 'Update Link Settings' } );

		await toggle.setChecked( false );
		await expect( dialog ).toContainText( 'Affibloom will disable the option Detect Opportunities' );
		await dialog.getByRole( 'button', { name: 'Only New Links' } ).click();
		expect( wp( 'post', 'meta', 'get', String( first ), '_affibloom_detect_opportunities' ) ).not.toBe( '0' );

		await toggle.setChecked( true );
		await dialog.getByRole( 'button', { name: 'Only New Links' } ).click();
		await toggle.setChecked( false );
		await dialog.getByRole( 'button', { name: 'Override All Links' } ).click();
		await expect( page.getByText( 'All products updated.' ) ).toBeVisible();
		expect( wp( 'post', 'meta', 'get', String( first ), '_affibloom_detect_opportunities' ) ).toBe( '0' );
		expect( wp( 'post', 'meta', 'get', String( second ), '_affibloom_detect_opportunities' ) ).toBe( '0' );

		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.getByText( 'Settings saved.' ) ).toBeVisible();
		const fresh = createProduct( { name: unique( 'F22 AC7 new' ) } );
		expect( wp( 'post', 'meta', 'get', String( fresh ), '_affibloom_detect_opportunities' ) ).toBe( '0' );
		setSettings( { detect_opportunities: true } );
		wp( 'eval', "do_action( 'rest_api_init' ); wp_set_current_user( 1 ); $r = new WP_REST_Request( 'POST', '/affibloom/v1/settings/link-defaults/apply' ); $r->set_body_params( array( 'key' => 'detect_opportunities', 'value' => true ) ); rest_do_request( $r );" );
	} );

	// AC12 (O4; R29-R31).
	test( "AC12 Content › post: the post's text links, product links first with Monetize on", async ( { page } ) => {
		const url = `https://www.example.com/${ unique( 'f22-ac12' ) }`;
		const product = createProduct( { name: unique( 'F22 AC12' ), url } );
		const title = unique( 'F22 AC12 post' );
		newPostWith( `<p><a href="https://plain-${ unique( 'x' ) }.example/a">an external link</a> and <a href="${ url }">the product</a>.</p>`, title );
		const host = Number( wp( 'post', 'list', '--post_type=post', `--title=${ title }`, '--field=ID' ) );

		await page.goto( `/wp-admin/admin.php?page=affibloom-opportunities&tab=content&s=${ encodeURIComponent( title ) }` );
		await page.getByRole( 'link', { name: title } ).click();
		await expect( page.getByRole( 'heading', { name: `Content › ${ title }` } ) ).toBeVisible();
		await expect( page ).toHaveURL( new RegExp( `tab=post.*post=${ host }|post=${ host }.*tab=post` ) );
		const rows = page.locator( '.affibloom-table--opportunities tbody tr' );
		await expect( rows ).toHaveCount( 2 );
		await expect( rows.first() ).toContainText( 'the product' );
		await expect( rows.first().getByRole( 'switch' ) ).toBeChecked();
		await expect( rows.nth( 1 ).getByRole( 'switch' ) ).not.toBeChecked();
		expect( product ).toBeGreaterThan( 0 );
	} );

	// AC13 (O1; R74-R80, D14, D15).
	test( 'AC13 nothing of keywords, Programs or Marketplace, and no request to Lasso', async ( { page, context } ) => {
		const hosts = new Set< string >();
		context.on( 'request', ( r ) => hosts.add( new URL( r.url() ).host ) );
		await page.goto( '/wp-admin/admin.php?page=affibloom-opportunities' );
		const text = await page.locator( '.affibloom-admin' ).innerText();
		for ( const word of [ 'Keyword', 'Programs', 'Marketplace' ] ) {
			expect( text ).not.toContain( word );
		}
		expect( [ ...hosts ].filter( ( host ) => /getlasso|lasso\.link|codedrink/.test( host ) ) ).toEqual( [] );
	} );
} );
