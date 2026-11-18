import { test, expect } from '@playwright/test';
import { createProduct, unique, wp } from '../utils';

// F11 Link health (docs/features/F11/requirements.md). Product links point at the test-only status stub
// (tests/E2E/link-stub.php, loaded on a local site only), served by the site itself: outgoing requests to other hosts
// are blocked on the Local copy, so those products record nothing (D6).

/** The stub's address answering with `status`. */
const stub = ( status: number ) => `${ test.info().project.use.baseURL }/wp-json/affibloom-stub/v1/status/${ status }`;

/** A product's row in affibloom_link_issues, or null. */
function issue( id: number ): Record< string, string > | null {
	const out = wp( 'eval', `global $wpdb; echo wp_json_encode( $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}affibloom_link_issues WHERE product_id = %d", ${ id } ), ARRAY_A ) );` );
	return JSON.parse( out ) as Record< string, string > | null;
}

/** Moves a product's last check an hour back, as if the hour of D4's second check had passed. */
function anHourLater( id: number ): void {
	wp( 'eval', `global $wpdb; $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}affibloom_link_issues SET last_checked_at = last_checked_at - INTERVAL 61 MINUTE, first_failed_at = first_failed_at - INTERVAL 61 MINUTE WHERE product_id = %d", ${ id } ) );` );
}

/** Forgets today's pass, so the daily event starts a new one. */
function newDay(): void {
	wp( 'option', 'delete', 'affibloom_link_check' );
}

/** The daily event, then batches until none is waiting; returns how many batches ran. */
function runPass(): number {
	wp( 'eval', 'do_action( "affibloom_daily" );' );
	return runBatches();
}

/** Runs waiting batches (at most 20), as WP-Cron would. */
function runBatches(): number {
	let ran = 0;
	while ( ran < 20 && 'yes' === wp( 'eval', 'echo false !== wp_next_scheduled( "affibloom_link_check_batch" ) && wp_next_scheduled( "affibloom_link_check_batch" ) <= time() ? "yes" : "no";' ) ) {
		wp( 'eval', 'wp_clear_scheduled_hook( "affibloom_link_check_batch" ); do_action( "affibloom_link_check_batch" );' );
		ran++;
	}
	return ran;
}

test.describe( 'F11 Link health', () => {
	let locationsOnly: unknown;

	// R5 (D5): with "locations only" on, only products used in content are checked; these products are in none.
	test.beforeAll( () => {
		const settings = JSON.parse( wp( 'option', 'get', 'affibloom_settings', '--format=json' ) || '{}' ) as Record< string, unknown >;
		locationsOnly = settings.pricing_locations_only;
		wp( 'eval', '$s = (array) get_option( "affibloom_settings", array() ); $s["pricing_locations_only"] = false; update_option( "affibloom_settings", $s );' );
	} );

	test.afterAll( () => {
		const value = undefined === locationsOnly ? 'unset( $s["pricing_locations_only"] );' : `$s["pricing_locations_only"] = ${ locationsOnly ? 'true' : 'false' };`;
		wp( 'eval', `$s = (array) get_option( "affibloom_settings", array() ); ${ value } update_option( "affibloom_settings", $s );` );
	} );

	test( 'AC1 a 404 link is flagged after two checks, an hour apart', () => {
		const id = createProduct( { name: unique( 'F11 AC1' ), url: stub( 404 ) } );
		newDay();
		runPass();
		expect( issue( id ) ).toMatchObject( { resolved: '0', failures: '1', pending_type: '404', issue_type: '' } );

		anHourLater( id );
		runBatches();
		const row = issue( id );
		expect( row ).toMatchObject( { resolved: '0', failures: '0', issue_type: '404' } );
		expect( row?.detection_date ).toBeTruthy();
	} );

	test( 'AC2 an Amazon product is never requested', () => {
		const id = createProduct( { name: unique( 'F11 AC2' ), type: 'amazon', asin: 'B0F11AC2XX', url: 'https://www.amazon.com/dp/B0F11AC2XX' } );
		newDay();
		runPass();
		expect( issue( id ) ).toBeNull();
	} );

	test( 'AC3 the daily pass checks each product once a day, in batches', () => {
		const ok = createProduct( { name: unique( 'F11 AC3' ), url: stub( 200 ) } );
		newDay();
		expect( runPass() ).toBeGreaterThan( 0 );
		expect( issue( ok ) ).toMatchObject( { issue_type: '200', resolved: '1' } );

		const pass = JSON.parse( wp( 'option', 'get', 'affibloom_link_check', '--format=json' ) ) as { total: number; checked: number; queue: number[] };
		expect( pass.queue ).toEqual( [] );
		expect( pass.checked ).toBe( pass.total );

		const checkedAt = issue( ok )?.last_checked_at;
		expect( runPass() ).toBe( 0 ); // The same day: nothing is queued.
		expect( issue( ok )?.last_checked_at ).toBe( checkedAt );
		expect( wp( 'cron', 'event', 'list', '--hook=affibloom_daily', '--fields=hook,recurrence', '--format=csv' ) ).toContain( 'affibloom_daily,1 day' );
	} );

	test( 'AC4 a blank destination is broken (404) after the pass, without a request', () => {
		const id = createProduct( { name: unique( 'F11 AC4' ), url: '' } );
		newDay();
		runPass();
		expect( issue( id ) ).toMatchObject( { issue_type: '404', resolved: '0', failures: '0' } );
	} );

	test( 'AC5 a broken product that answers 200 is cleared; broken again, it is dated anew', () => {
		const id = createProduct( { name: unique( 'F11 AC5' ), url: stub( 404 ) } );
		newDay();
		runPass();
		anHourLater( id );
		runBatches();
		const first = issue( id );
		expect( first ).toMatchObject( { issue_type: '404', resolved: '0' } );

		wp( 'post', 'meta', 'update', String( id ), '_affibloom_url', stub( 200 ) );
		newDay();
		runPass();
		expect( issue( id ) ).toMatchObject( { issue_type: '404', resolved: '1' } ); // Type and date kept (R13).

		wp( 'post', 'meta', 'update', String( id ), '_affibloom_url', stub( 410 ) );
		wp( 'eval', `global $wpdb; $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}affibloom_link_issues SET detection_date = detection_date - INTERVAL 3 DAY WHERE product_id = %d", ${ id } ) );` );
		const old = issue( id )?.detection_date;
		newDay();
		runPass();
		anHourLater( id );
		runBatches();
		const again = issue( id );
		expect( again ).toMatchObject( { issue_type: '410', resolved: '0' } );
		expect( again?.detection_date ).not.toBe( old );
	} );

	test( 'AC6 saving a product with a new destination checks it at once, and a 404 an hour later', async ( { page } ) => {
		const id = createProduct( { name: unique( 'F11 AC6' ), url: stub( 200 ) } );
		const due = () => wp( 'eval', `echo false === wp_next_scheduled( "affibloom_link_check_product", array( ${ id } ) ) ? "no" : "yes";` );
		/** Runs the product's waiting check, as WP-Cron would. */
		const runCheck = () => wp( 'eval', `wp_clear_scheduled_hook( "affibloom_link_check_product", array( ${ id } ) ); do_action( "affibloom_link_check_product", ${ id } );` );

		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }` );
		await page.locator( '#affibloom-url' ).fill( `${ stub( 404 ) }?ac6=${ id }` );
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.locator( '.affibloom-toast' ).last() ).toContainText( 'Product saved' );

		expect( due() ).toBe( 'yes' );
		runCheck();
		expect( issue( id ) ).toMatchObject( { failures: '1', pending_type: '404', resolved: '0' } );
		expect( due() ).toBe( 'yes' ); // The second check, an hour later (D4).
		anHourLater( id );
		runCheck();
		expect( issue( id ) ).toMatchObject( { issue_type: '404', resolved: '0', failures: '0' } );

		// Saved again within the hour with the same link: no check.
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }` );
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.locator( '.affibloom-toast' ).last() ).toContainText( 'Product saved' );
		expect( due() ).toBe( 'no' );

		// A working link: checked at once, the tick's row.
		await page.locator( '#affibloom-url' ).fill( `${ stub( 200 ) }?ac6=${ id }` );
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.locator( '.affibloom-toast' ).last() ).toContainText( 'Product saved' );
		runCheck();
		expect( issue( id ) ).toMatchObject( { resolved: '1' } );
	} );
} );

// AC7 (Task 30C): Amazon products through the product API, answered by the test-only Amazon stub (tests/E2E/amazon-stub.php,
// scenario "ok": ASINs starting B0NOTFOUND are not found, B0OUTSTOCK out of stock, others in stock).
test.describe( 'F11 Link health: Amazon products', () => {
	let saved: string | null = null;
	let locationsOnly: unknown;

	/** Stores the suite's test credentials (null removes them). */
	const credentials = ( on: boolean ) => {
		try {
			wp( 'option', 'delete', 'affibloom_amazon_credentials' );
		} catch {
			// None stored.
		}
		if ( on ) {
			wp( 'option', 'add', 'affibloom_amazon_credentials', JSON.stringify( { credential_id: 'amzn1.application-oa2-client.e2e', secret: 'e2e-secret', partner_tag: 'vbvault-20', version: '3.1', verified_signature: '' } ), '--format=json', '--autoload=no' );
		}
	};

	const amazonProduct = ( asin: string ) => createProduct( { name: unique( `F11 AC7 ${ asin }` ), type: 'amazon', asin, url: `https://www.amazon.com/dp/${ asin }` } );

	test.beforeAll( () => {
		try {
			saved = wp( 'option', 'get', 'affibloom_amazon_credentials', '--format=json' );
		} catch {
			saved = null;
		}
		const settings = JSON.parse( wp( 'option', 'get', 'affibloom_settings', '--format=json' ) || '{}' ) as Record< string, unknown >;
		locationsOnly = settings.pricing_locations_only;
		wp( 'eval', '$s = (array) get_option( "affibloom_settings", array() ); $s["pricing_locations_only"] = false; update_option( "affibloom_settings", $s );' );
		wp( 'option', 'update', 'affibloom_test_amazon_stub', 'ok' );
		wp( 'option', 'update', 'affibloom_test_amazon_requests', '0' );
	} );

	test.afterAll( () => {
		wp( 'option', 'update', 'affibloom_test_amazon_stub', '' );
		credentials( false );
		if ( saved ) {
			wp( 'option', 'add', 'affibloom_amazon_credentials', saved, '--format=json', '--autoload=no' );
		}
		const value = undefined === locationsOnly ? 'unset( $s["pricing_locations_only"] );' : `$s["pricing_locations_only"] = ${ locationsOnly ? 'true' : 'false' };`;
		wp( 'eval', `$s = (array) get_option( "affibloom_settings", array() ); ${ value } update_option( "affibloom_settings", $s );` );
	} );

	test( 'AC7 the product API decides: not found is broken, no stock is out of stock, back in stock is cleared', () => {
		credentials( true );
		const gone = amazonProduct( 'B0NOTFOUND7' );
		const out = amazonProduct( 'B0OUTSTOCK7' );
		const fine = amazonProduct( 'B0F11AC7OK' );
		newDay();
		runPass();
		expect( issue( gone ) ).toMatchObject( { issue_type: '404', resolved: '0', failures: '0' } );
		expect( issue( out ) ).toMatchObject( { issue_type: '000', resolved: '0' } );
		expect( issue( fine ) ).toMatchObject( { issue_type: '200', resolved: '1' } );

		// Back in stock: the stub answers in stock for another ASIN; the next day's pass clears it, keeping its code (R13).
		wp( 'post', 'meta', 'update', String( out ), '_affibloom_asin', 'B0F11AC7BACK' );
		newDay();
		runPass();
		expect( issue( out ) ).toMatchObject( { issue_type: '000', resolved: '1' } );
		expect( Number( wp( 'option', 'get', 'affibloom_test_amazon_requests' ) ) ).toBeGreaterThan( 0 );
	} );

	test( 'AC7 without credentials Amazon products are not checked', () => {
		credentials( false );
		wp( 'option', 'update', 'affibloom_test_amazon_requests', '0' );
		const id = amazonProduct( 'B0OUTSTOCK8' );
		newDay();
		runPass();
		expect( issue( id ) ).toBeNull();
		expect( wp( 'option', 'get', 'affibloom_test_amazon_requests' ) ).toBe( '0' );
	} );
} );

// Tasks 31A-31D: the screens. A product's row is put in a state directly in affibloom_link_issues.
test.describe( 'F11 Link health: the Dashboard', () => {
	/** Gives a product a link-health row: an open issue of `type` found `daysAgo` days ago, or a checked one. */
	function setIssue( id: number, type: string, open: boolean, daysAgo = 0 ): void {
		wp( 'eval', `global $wpdb; $wpdb->replace( $wpdb->prefix . "affibloom_link_issues", array( "product_id" => ${ id }, "issue_type" => "${ type }", "checked_url" => "x", "detection_date" => gmdate( "Y-m-d H:i:s", time() - ${ daysAgo } * DAY_IN_SECONDS ), "resolved" => ${ open ? 0 : 1 }, "last_checked_at" => gmdate( "Y-m-d H:i:s" ), "failures" => 0 ) );` );
	}

	/** Sets "Days before out of stock alerts". */
	function graceDays( days: number ): void {
		wp( 'eval', `$s = (array) get_option( "affibloom_settings", array() ); $s["days_before_out_of_stock_alerts"] = ${ days }; update_option( "affibloom_settings", $s );` );
	}

	test.afterAll( () => graceDays( 0 ) );

	test( 'AC8 the Alert cell shows each state with its tooltip and days line', async ( { page } ) => {
		const name = unique( 'F11 AC8' );
		const broken = createProduct( { name: `${ name } broken`, url: 'https://shop.example/a' } );
		const out = createProduct( { name: `${ name } out`, url: 'https://shop.example/b' } );
		const ok = createProduct( { name: `${ name } ok`, url: 'https://shop.example/c' } );
		const fresh = createProduct( { name: `${ name } fresh`, url: 'https://shop.example/d' } );
		setIssue( broken, '404', true, 2 );
		setIssue( out, '000', true, 0 );
		setIssue( ok, '200', false );

		await page.goto( `/wp-admin/admin.php?page=affibloom&s=${ encodeURIComponent( name ) }` );
		const cell = ( id: number ) => page.locator( `tr[data-product-id="${ id }"] .affibloom-col-alert` );
		await expect( cell( broken ).locator( '.affibloom-alert--broken' ) ).toHaveAttribute( 'data-tip', /^Broken since [A-Z][a-z]{2} \d{1,2}, \d{4}$/ );
		await expect( cell( broken ).locator( '.affibloom-alert--broken' ) ).toHaveAttribute( 'href', new RegExp( `page=affibloom-product&id=${ broken }$` ) );
		await expect( cell( broken ) ).toContainText( '3 days' );
		await expect( cell( out ).locator( '.affibloom-alert--out-of-stock' ) ).toHaveAttribute( 'data-tip', /^Out of stock since / );
		await expect( cell( out ) ).toContainText( '1 day' );
		await expect( cell( ok ).locator( '.affibloom-alert--checked' ) ).toHaveAttribute( 'data-tip', 'Link status checked. No alert issues detected.' );
		await expect( cell( fresh ).locator( '.affibloom-alert--pending' ) ).toHaveAttribute( 'data-tip', 'Processing. Please check back in 24 hours.' );

		// The tooltip is reached by the keyboard too.
		await cell( ok ).locator( '.affibloom-alert--checked' ).focus();
		await expect( cell( ok ).locator( '.affibloom-alert--checked' ) ).toBeFocused();
	} );

	test( 'AC9 the Alert header sorts by detection date: ascending, descending, off', async ( { page } ) => {
		const name = unique( 'F11 AC9' );
		const older = createProduct( { name: `${ name } older`, url: 'https://shop.example/e' } );
		const newer = createProduct( { name: `${ name } newer`, url: 'https://shop.example/f' } );
		setIssue( older, '404', true, 5 );
		setIssue( newer, '404', true, 1 );
		await page.goto( `/wp-admin/admin.php?page=affibloom&s=${ encodeURIComponent( name ) }` );
		const ids = async () => page.locator( 'tbody tr[data-product-id]' ).evaluateAll( ( rows ) => rows.map( ( row ) => Number( row.getAttribute( 'data-product-id' ) ) ) );
		const header = page.locator( 'th.affibloom-col-alert' );

		await header.getByRole( 'button' ).click();
		await expect( header ).toHaveAttribute( 'aria-sort', 'ascending' );
		await expect( page ).toHaveURL( /order_by=detection_date&order_type=asc/ );
		expect( await ids() ).toEqual( [ older, newer ] );

		await header.getByRole( 'button' ).click();
		await expect( header ).toHaveAttribute( 'aria-sort', 'descending' );
		expect( await ids() ).toEqual( [ newer, older ] );

		await header.getByRole( 'button' ).click();
		await expect( header ).toHaveAttribute( 'aria-sort', 'none' );
		await expect( page ).not.toHaveURL( /order_by/ );
	} );

	test( 'AC10 the Broken links and Out of stock tabs filter the list, follow the search, and keep in a saved view', async ( { page } ) => {
		const name = unique( 'F11 AC10' );
		const broken = createProduct( { name: `${ name } broken`, url: 'https://shop.example/h' } );
		const out = createProduct( { name: `${ name } out`, url: 'https://shop.example/i' } );
		createProduct( { name: `${ name } fine`, url: 'https://shop.example/j' } );
		setIssue( broken, '404', true, 1 );
		setIssue( out, '000', true, 1 );
		await page.goto( `/wp-admin/admin.php?page=affibloom&s=${ encodeURIComponent( name ) }` );
		const brokenTab = page.locator( 'a.affibloom-filter-broken' );
		await expect( brokenTab ).toHaveAccessibleName( /Broken links \(1\)/ );
		await expect( brokenTab ).toHaveAttribute( 'data-tip', /^See broken URLs/ );
		await expect( page.locator( 'a.affibloom-filter-out-of-stock' ) ).toHaveAccessibleName( /Out of stock \(1\)/ );

		await brokenTab.click();
		await expect( page ).toHaveURL( /filter=broken-links/ );
		await expect( brokenTab ).toHaveAttribute( 'aria-current', 'true' );
		await expect( page.locator( 'tbody tr[data-product-id]' ) ).toHaveCount( 1 );
		await expect( page.locator( `tr[data-product-id="${ broken }"]` ) ).toBeVisible();

		// Saved as a view, the tab comes back with it.
		await page.locator( '[aria-controls="affibloom-filters-panel"]' ).click();
		await page.getByLabel( 'Save as' ).fill( `${ name } view` );
		await page.locator( '#affibloom-filters-panel' ).getByRole( 'button', { name: 'Save', exact: true } ).click();
		await expect( page.getByRole( 'link', { name: `${ name } view` } ) ).toBeVisible();
		await brokenTab.click();
		await expect( page ).not.toHaveURL( /filter=/ );
		await expect( page.locator( 'tbody tr[data-product-id]' ) ).toHaveCount( 3 );
		await page.getByRole( 'link', { name: `${ name } view` } ).click();
		await expect( page ).toHaveURL( /filter=broken-links/ );

		// Reset filters clears it.
		await page.locator( '[data-views] .affibloom-pill' ).first().click();
		await expect( page ).not.toHaveURL( /filter=/ );
		await expect( brokenTab ).not.toHaveAttribute( 'aria-current', 'true' );
	} );

	test( 'AC12 the product page shows the broken and out-of-stock bars; × hides one for 7 days', async ( { page } ) => {
		const broken = createProduct( { name: unique( 'F11 AC12 broken' ), url: 'https://shop.example/k' } );
		const out = createProduct( { name: unique( 'F11 AC12 out' ), url: 'https://shop.example/l' } );
		setIssue( broken, '410', true, 1 );
		setIssue( out, '000', true, 1 );

		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ out }` );
		await expect( page.locator( '[data-issue-bar="out-of-stock"]' ) ).toContainText( 'This product may be out of stock.' );

		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ broken }` );
		const bar = page.locator( '[data-issue-bar="broken"]' );
		await expect( bar ).toContainText( 'Your URL is broken.' );
		await expect( bar ).toHaveAttribute( 'role', 'status' );
		const closed = page.waitForResponse( ( response ) => response.url().includes( `/products/${ broken }/alerts/dismiss` ) );
		await bar.getByRole( 'button', { name: 'Hide for 7 days' } ).click();
		expect( ( await closed ).status() ).toBe( 200 );
		await expect( bar ).toBeHidden();
		await page.reload();
		await expect( page.locator( '[data-issue-bar="broken"]' ) ).toHaveCount( 0 );
		const until = Number( wp( 'post', 'meta', 'get', String( broken ), '_affibloom_dismiss_broken' ) );
		expect( until - Math.floor( Date.now() / 1000 ) ).toBeGreaterThan( 7 * 86400 - 120 );
	} );

	test( 'AC13 Export CSV downloads the tab as a WP All Import CSV; disabled when empty, hidden elsewhere', async ( { page } ) => {
		const name = unique( 'F11 AC13' );
		const id = createProduct( { name, url: 'https://shop.example/m', badge: '=HYPERLINK("x")' } );
		setIssue( id, '404', true, 1 );
		await page.goto( `/wp-admin/admin.php?page=affibloom&s=${ encodeURIComponent( name ) }` );
		await expect( page.locator( '[data-export-csv]' ) ).toHaveCount( 0 );

		await page.locator( 'a.affibloom-filter-broken' ).click();
		const button = page.getByRole( 'button', { name: 'Export CSV' } );
		await expect( button ).toHaveAttribute( 'title', 'Export all filtered links to WP All Import CSV' );
		const download = page.waitForEvent( 'download' );
		await button.click();
		const file = await download;
		expect( file.suggestedFilename() ).toMatch( /^affibloom-broken-links-\d{4}-\d{2}-\d{2}\.csv$/ );
		const csv = require( 'node:fs' ).readFileSync( await file.path(), 'utf8' ) as string;
		const [ header, row ] = csv.trim().split( '
' );
		expect( header.split( ',' ) ).toHaveLength( 24 );
		expect( row ).toContain( 'Broken (404)' );
		expect( row ).toContain( '"	=HYPERLINK(""x"")"' );
		await expect( page.locator( '.affibloom-toast' ).last() ).toContainText( 'Exported 1 link.' );

		await page.goto( `/wp-admin/admin.php?page=affibloom&filter=out-of-stock&s=${ encodeURIComponent( name ) }` );
		await expect( page.getByRole( 'button', { name: 'Export CSV' } ) ).toBeDisabled();
		await expect( page.getByRole( 'button', { name: 'Export CSV' } ) ).toHaveAttribute( 'title', 'No links to export for the current filters.' );
	} );

	test( 'AC11 an out-of-stock issue younger than the grace days shows nowhere', async ( { page } ) => {
		const name = unique( 'F11 AC11' );
		const id = createProduct( { name, url: 'https://shop.example/g' } );
		setIssue( id, '000', true, 2 );
		graceDays( 3 );
		await page.goto( `/wp-admin/admin.php?page=affibloom&s=${ encodeURIComponent( name ) }` );
		await expect( page.locator( `tr[data-product-id="${ id }"] .affibloom-alert` ) ).toHaveCount( 0 );

		graceDays( 2 );
		await page.reload();
		await expect( page.locator( `tr[data-product-id="${ id }"] .affibloom-alert--out-of-stock` ) ).toBeVisible();
	} );
} );
