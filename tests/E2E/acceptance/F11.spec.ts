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
} );
