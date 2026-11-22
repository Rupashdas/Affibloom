import { test, expect } from '@playwright/test';
import { blockTrackers, createProduct, newPostWith, setSettings, unique, wp } from '../utils';

// F22 Opportunities (docs/features/F22/requirements.md).
test.describe( 'F22 Opportunities', () => {
	test.beforeEach( async ( { context } ) => {
		await blockTrackers( context );
		// Lasso's default, which new products take: a run that stopped inside AC7 would leave it off.
		setSettings( { detect_opportunities: true } );
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
		await expect( page.locator( '.affibloom-table--opportunities thead' ).getByRole( 'columnheader' ) ).toHaveText( [ /URL/, /Content/, /Suggestion/, /Type/, /Monetize/, /Dismiss/ ] );

		await page.getByLabel( 'Search Opportunities' ).fill( `${ domain } item-1` );
		await page.getByLabel( 'Search Opportunities' ).press( 'Enter' );
		await expect( page.locator( '[data-found]' ) ).toHaveText( '3 Found' ); // item-1, item-10, item-11
		// Each click redraws the list in place: the next one waits for the address to carry the sort.
		await page.getByRole( 'button', { name: 'URL', exact: true } ).click();
		await expect( page ).toHaveURL( /order=asc/ );
		await page.getByRole( 'button', { name: 'URL', exact: true } ).click();
		await expect( page ).toHaveURL( /order=desc/ );
		await expect( page.locator( '.affibloom-table--opportunities tbody tr' ).first() ).toContainText( 'item-11' );

		await page.getByLabel( 'Search Opportunities' ).fill( 'nothing-like-this' );
		await page.getByLabel( 'Search Opportunities' ).press( 'Enter' );
		await expect( page.locator( '#affibloom-opportunities-list' ).getByText( 'No opportunities found.' ) ).toBeVisible();

		const intro = page.locator( '[data-intro="links"]' );
		if ( await intro.count() ) {
			// The box hides at once; its choice is saved in the background, which the reload waits for.
			const saved = page.waitForResponse( ( r ) => r.url().includes( '/opportunities/intros/' ) );
			await intro.getByRole( 'button', { name: 'Close this box for good' } ).click();
			await saved;
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
		await expect( page.locator( '#affibloom-opportunities-list' ).getByText( "Looks like we're coming up empty." ) ).toBeVisible();
		await page.getByLabel( 'Post type' ).selectOption( 'post' );
		await page.getByLabel( 'Min link clicks' ).fill( '1' );
		await page.getByLabel( 'Min link clicks' ).press( 'Enter' );
		await expect( page.locator( '#affibloom-opportunities-list' ).getByText( "Looks like we're coming up empty." ) ).toBeVisible(); // no product, no clicks
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

	// AC8 (O7; R44-R49, D10-D12).
	test( 'AC8 Dismiss hides the link everywhere it is suggested, survives a re-save, and Undo brings it back', async ( { page } ) => {
		const { product, title, domain } = fixture( 2 );
		const host = Number( wp( 'post', 'list', '--post_type=post', `--title=${ title }`, '--field=ID' ) );
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ product }&tab=opportunities` );
		await expect( page.locator( '[data-tab-count="opportunities"]' ) ).toHaveText( '2' );

		await page.getByRole( 'button', { name: `Dismiss https://www.${ domain }/item-0` } ).click();
		const dialog = page.getByRole( 'dialog', { name: 'Dismiss This Opportunity' } );
		await expect( dialog ).toContainText( 'If you dismiss this, it will no longer appear as an opportunity.' );
		await dialog.getByRole( 'button', { name: 'Cancel' } ).click();
		await expect( page.locator( '[data-found]' ) ).toHaveText( '2 Found' );

		await page.getByRole( 'button', { name: `Dismiss https://www.${ domain }/item-0` } ).click();
		await dialog.getByRole( 'button', { name: 'Dismiss', exact: true } ).click();
		await expect( page.locator( '[data-found]' ) ).toHaveText( '1 Found' );
		await expect( page.locator( '[data-tab-count="opportunities"]' ) ).toHaveText( '1' );
		await expect( page.locator( '.affibloom-card--table' ) ).toBeFocused();
		await expect( page.locator( '.affibloom-toast' ) ).toContainText( 'Opportunity dismissed.' );

		// The post saved again re-indexes it: still dismissed. Domains' Detected Links still list it (D12).
		wp( 'post', 'update', String( host ), '--post_status=publish' );
		await page.goto( `/wp-admin/admin.php?page=affibloom-opportunities&product=${ product }` );
		await expect( page.locator( '[data-found]' ) ).toHaveText( '1 Found' );
		await page.goto( `/wp-admin/admin.php?page=affibloom-opportunities&tab=domain&domain=${ domain }` );
		await expect( page.locator( '.affibloom-list' ) ).toContainText( 'item-0' );

		// Undo, from a fresh dismissal's notice.
		await page.goto( `/wp-admin/admin.php?page=affibloom-opportunities&product=${ product }` );
		await page.getByRole( 'button', { name: `Dismiss https://www.${ domain }/item-1` } ).click();
		await page.getByRole( 'dialog', { name: 'Dismiss This Opportunity' } ).getByRole( 'button', { name: 'Dismiss', exact: true } ).click();
		await expect( page.locator( '[data-found]' ) ).toHaveText( '0 Found' );
		await page.locator( '.affibloom-toast' ).getByRole( 'button', { name: 'Undo' } ).click();
		await expect( page.locator( '[data-found]' ) ).toHaveText( '1 Found' );
	} );

	// AC9 (O8; R50-R56, D13).
	test( "AC9 See opportunities: the tile's count, its tab sorted by the count, the lightbulb to the product's tab", async ( { page } ) => {
		const { product } = fixture( 2 );
		const name = wp( 'post', 'get', String( product ), '--field=post_title' );
		await page.goto( `/wp-admin/admin.php?page=affibloom&s=${ encodeURIComponent( name ) }` );
		const tile = page.locator( '[data-alert-tab="opportunities"]' );
		await expect( tile.locator( '[data-alert-count]' ) ).toHaveText( '1' );

		// All links: the bulb counts rows (2 links), singular and plural follow the number.
		const row = page.locator( '.affibloom-table tbody tr', { hasText: name } );
		await expect( row.locator( '.affibloom-alert--opportunity' ) ).toHaveAttribute( 'data-tip', 'This URL has 2 possible opportunities.' );

		await tile.click();
		await expect( page ).toHaveURL( /filter=opportunities&?.*orderby=opportunity_count/ );
		await expect( tile ).toHaveAttribute( 'aria-current', 'true' );
		await expect( page.locator( '.affibloom-table tbody tr' ) ).toHaveCount( 1 );
		// The tab counts posts: both links are in one post.
		await expect( row.locator( '.affibloom-alert--opportunity' ) ).toHaveAttribute( 'data-tip', 'This URL has 1 possible opportunity.' );
		await expect( page.locator( 'th.affibloom-col-alert' ) ).toHaveAttribute( 'aria-sort', 'descending' );

		await row.locator( '.affibloom-alert--opportunity' ).click();
		await expect( page ).toHaveURL( new RegExp( `page=affibloom-product&id=${ product }&tab=opportunities` ) );

		// The count's sort outside its tab falls back to Last Updated and says so (F19 R5).
		await page.goto( '/wp-admin/admin.php?page=affibloom&orderby=opportunity_count&order=desc' );
		await expect( page.getByText( 'Sorting by Opportunities is only available on the Opportunities tab.' ) ).toBeVisible();
	} );

	// AC10 (O9; R57-R63).
	test( 'AC10 Content: Export CSV downloads the unmonetized anchors of the filtered posts, formula-safe', async ( { page } ) => {
		const title = unique( 'F22 AC10' );
		newPostWith( `<p>Try <a href="https://plain-${ unique( 'x' ) }.example/a">=SUM(1)</a> and <a href="https://plain-${ unique( 'y' ) }.example/b">two</a>.</p>`, title );
		await page.goto( `/wp-admin/admin.php?page=affibloom-opportunities&tab=content&s=${ encodeURIComponent( title ) }` );
		const button = page.getByRole( 'button', { name: 'Export CSV' } );
		await expect( button ).toBeEnabled();
		await expect( button ).toHaveAttribute( 'title', 'Export unmonetized anchors to CSV' );

		const download = page.waitForEvent( 'download' );
		await button.click();
		const file = await download;
		expect( file.suggestedFilename() ).toMatch( /^affibloom-content-opportunities-\d{4}-\d{2}-\d{2}\.csv$/ );
		const csv = ( require( 'node:fs' ).readFileSync( await file.path(), 'utf8' ) as string ).trim().split( '\n' );
		expect( csv[ 0 ] ).toBe( 'post_title,post_url,post_id,post_type,post_categories,post_modified,content_link_clicks,anchor_text,anchor_context,destination_url,link_domain,link_type,display_type,link_location_id,unmonetized_link_count,suggested_product_title,suggested_product_id,suggested_product_edit_url' );
		expect( csv ).toHaveLength( 3 );
		expect( csv[ 1 ] ).toContain( '\t=SUM(1)' );
		await expect( page.locator( '.affibloom-toast' ) ).toContainText( 'Exported 2 anchors.' );

		// Nothing to export: the button is off, with Lasso's title.
		await page.locator( '#affibloom-content-filters [name="min_clicks"]' ).fill( '999999' );
		await page.locator( '#affibloom-content-filters [name="min_clicks"]' ).dispatchEvent( 'change' );
		await expect( button ).toBeDisabled();
		await expect( button ).toHaveAttribute( 'title', 'No content opportunities to export for the current filters.' );
	} );

	// AC11 (O10; R64-R73, N1-N3, D21, D22).
	test( "AC11 Monetize prints the product's link at render time; the stored post never changes; off restores it", async ( { page } ) => {
		const { product, title } = fixture( 1 );
		const name = wp( 'post', 'get', String( product ), '--field=post_title' );
		const host = Number( wp( 'post', 'list', '--post_type=post', `--title=${ title }`, '--field=ID' ) );
		const stored = wp( 'post', 'get', String( host ), '--field=post_content' );

		await page.goto( `/wp-admin/admin.php?page=affibloom-opportunities&product=${ product }` );
		await expect( page.locator( '[data-found]' ) ).toHaveText( '1 Found' );
		const row = page.locator( '.affibloom-table--opportunities tbody tr' ).first();

		// Closing the dialog without a choice turns the switch off again.
		await row.locator( '.affibloom-toggle' ).click();
		const dialog = page.getByRole( 'dialog', { name: 'Monetize' } );
		await expect( dialog ).toBeVisible();
		await expect( dialog.getByRole( 'button', { name: 'Create a Link' } ) ).toBeVisible();
		await page.keyboard.press( 'Escape' );
		await expect( row.getByRole( 'switch' ) ).not.toBeChecked();

		await row.locator( '.affibloom-toggle' ).click();
		await dialog.getByRole( 'searchbox' ).fill( name );
		await dialog.getByRole( 'button', { name: `Monetize: ${ name }` } ).click();
		await expect( page.locator( '[data-found]' ) ).toHaveText( '0 Found' );

		// The front end prints the product's link; the stored post is byte for byte the same.
		await page.goto( `/?p=${ host }` );
		await expect( page.locator( `a[data-affibloom-id="${ product }"]` ) ).toHaveCount( 1 );
		expect( wp( 'post', 'get', String( host ), '--field=post_content' ) ).toBe( stored );

		// A post's links: on, "Monetized with …", Edit in post; off brings the stored link back.
		await page.goto( `/wp-admin/admin.php?page=affibloom-opportunities&tab=post&post=${ host }` );
		const line = page.locator( 'tbody tr', { hasText: `Monetized with ${ name }` } );
		await expect( line.getByRole( 'switch' ) ).toBeChecked();
		await expect( line.getByRole( 'link', { name: 'Edit in post' } ) ).toHaveAttribute( 'href', /affibloom_location=\d+/ );
		await line.locator( '.affibloom-toggle' ).click();
		await expect( page.getByText( `Monetized with ${ name }` ) ).toHaveCount( 0 );
		await page.goto( `/?p=${ host }` );
		await expect( page.locator( `a[data-affibloom-id="${ product }"]` ) ).toHaveCount( 0 );
		expect( wp( 'post', 'get', String( host ), '--field=post_content' ) ).toBe( stored );
	} );

	test( 'AC11 a link tied by its own address is on and disabled, and says why', async ( { page } ) => {
		const url = `https://www.example.com/${ unique( 'f22-ac11-tied' ) }`;
		createProduct( { name: unique( 'F22 AC11 tied' ), url } );
		const title = unique( 'F22 AC11 tied post' );
		newPostWith( `<p>Get <a href="${ url }">it</a>.</p>`, title );
		const host = Number( wp( 'post', 'list', '--post_type=post', `--title=${ title }`, '--field=ID' ) );

		await page.goto( `/wp-admin/admin.php?page=affibloom-opportunities&tab=post&post=${ host }` );
		const toggle = page.locator( 'tbody tr' ).first().getByRole( 'switch' );
		await expect( toggle ).toBeChecked();
		await expect( toggle ).toBeDisabled();
		await expect( page.locator( 'tbody tr .affibloom-toggle' ).first() ).toHaveAttribute( 'title', "This link is in the post's content; edit the post to change it." );
	} );

	// AC11, N3 (E17; Task GF6): a link tied by its own address with an original URL (Lasso's original_link_slug, which
	// Task 26Q brings) is turned back to that URL at render time; the stored post never changes.
	test( 'AC11 a link tied by its own address with an original URL is turned back to it', async ( { page } ) => {
		const url = `https://www.example.com/${ unique( 'f22-n3' ) }`;
		const original = `https://shop.example/${ unique( 'f22-n3-original' ) }`;
		createProduct( { name: unique( 'F22 N3' ), url } );
		const title = unique( 'F22 N3 post' );
		newPostWith( `<p>Get <a href="${ url }">the shoe</a>.</p>`, title );
		const host = Number( wp( 'post', 'list', '--post_type=post', `--title=${ title }`, '--field=ID' ) );
		const stored = wp( 'post', 'get', String( host ), '--field=post_content' );
		expect( wp( 'eval', `echo ( new Affibloom\\Extension\\OpportunityAccess( new Affibloom\\Infrastructure\\WordPress\\TableLinkLocationRepository() ) )->set_original_url( ${ host }, '${ url }', '${ original }' );` ) ).toBe( '1' );

		await page.goto( `/wp-admin/admin.php?page=affibloom-opportunities&tab=post&post=${ host }` );
		const toggle = page.locator( 'tbody tr' ).first().getByRole( 'switch' );
		await expect( toggle ).toBeChecked();
		await expect( toggle ).toBeEnabled();
		await page.locator( 'tbody tr .affibloom-toggle' ).first().click();
		await expect( page.locator( 'tbody tr .affibloom-opportunity__main' ).first() ).toHaveText( original );
		await expect( page.locator( 'tbody tr' ).first().getByRole( 'switch' ) ).not.toBeChecked();

		await page.goto( `/?p=${ host }` );
		await expect( page.getByRole( 'link', { name: 'the shoe' } ) ).toHaveAttribute( 'href', original );
		expect( wp( 'post', 'get', String( host ), '--field=post_content' ) ).toBe( stored );
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
