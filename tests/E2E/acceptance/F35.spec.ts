import { test, expect } from '@playwright/test';
import { blockTrackers, newPostWith, setSettings, unique, wp } from '../utils';

// F35 Amazon auto-monetization (docs/features/F35/requirements.md, AC1-AC8). The affiliate-network ACs are C2's.
//
// Note for the run: the import makes a product for every untied Amazon link on the copy (up to 100 a run). On a copy
// whose Amazon API is connected (C1), each new product asks Amazon for its data once.

const run = () => wp( 'cron', 'event', 'run', 'affibloom_amazon_import' );
const amazonProducts = ( asin: string ) =>
	Number( wp( 'post', 'list', '--post_type=affibloom_product', '--meta_key=_affibloom_asin', `--meta_value=${ asin }`, '--format=count' ) );
// A made-up ASIN, unique per run: 10 characters, B0 then 8 letters or digits.
const asin = () => ( 'B0' + unique( 'f35' ).replace( /[^a-z0-9]/gi, '' ).toUpperCase() ).slice( 0, 10 ).padEnd( 10, 'X' );

test.describe( 'F35 Amazon auto-monetization', () => {
	test.beforeEach( async ( { context } ) => {
		await blockTrackers( context );
		setSettings( { auto_monetize_amazon: true } );
	} );

	test.afterAll( () => {
		setSettings( { auto_monetize_amazon: false } ); // Local's value: no import between runs
	} );

	test( 'AC1 an Amazon link in a post becomes one product at the next run; again: none; the post is unchanged', () => {
		const code = asin();
		const content = `<p>Buy <a href="https://www.amazon.com/dp/${ code }?tag=other-20">this</a>.</p>`;
		const id = newPostWith( content ).replace( '/?p=', '' );
		run();
		expect( amazonProducts( code ) ).toBe( 1 );
		run();
		expect( amazonProducts( code ) ).toBe( 1 );
		expect( wp( 'post', 'get', id, '--field=post_content' ) ).toBe( content );
	} );

	test( 'AC1 a retired ASIN is not imported', () => {
		const code = asin();
		newPostWith( `<p><a href="https://www.amazon.com/dp/${ code }">old</a></p>` );
		run();
		const product = wp( 'post', 'list', '--post_type=affibloom_product', '--meta_key=_affibloom_asin', `--meta_value=${ code }`, '--field=ID' );
		wp( 'post', 'update', product, '--post_status=affibloom_retired' );
		newPostWith( `<p><a href="https://www.amazon.com/dp/${ code }">again</a></p>` );
		run();
		expect( amazonProducts( code ) ).toBe( 0 ); // the published count: the retired one stays retired
	} );

	test( "AC2 the front end prints the product's link with the site's tag, rel and target", async ( { page } ) => {
		const code = asin();
		const id = newPostWith( `<p><a href="https://www.amazon.com/dp/${ code }?tag=other-20">this</a></p>` ).replace( '/?p=', '' );
		run();
		const product = wp( 'post', 'list', '--post_type=affibloom_product', '--meta_key=_affibloom_asin', `--meta_value=${ code }`, '--field=ID' );
		await page.goto( `/?p=${ id }` );
		const link = page.locator( `a[data-affibloom-id="${ product }"]` );
		await expect( link ).toHaveAttribute( 'href', /tag=vbvault-20/ );
		await expect( link ).toHaveAttribute( 'rel', /nofollow/ );
	} );

	// AC3 (AM2; R28, D14): amzn.to answers 301 to the product page (stubbed: the suite never reaches Amazon's hosts).
	test( 'AC3 an Amazon short link is resolved once and tied to its product; the post keeps amzn.to', () => {
		const code = asin();
		const short = `https://amzn.to/${ unique( 's' ).replace( /[^a-z0-9]/gi, '' ).slice( 0, 7 ) }`;
		const content = `<p><a href="${ short }">short</a></p>`;
		const id = newPostWith( content ).replace( '/?p=', '' );
		const stub = `add_filter( "pre_http_request", static function ( $pre, $args, $url ) { if ( "${ short }" === $url ) { update_option( "afb_test_short_asked", (int) get_option( "afb_test_short_asked", 0 ) + 1 ); return array( "response" => array( "code" => 301, "message" => "Moved" ), "headers" => array( "location" => "https://www.amazon.com/dp/${ code }" ), "body" => "", "cookies" => array() ); } return str_contains( $url, "amazon" ) ? new WP_Error( "http_request_not_executed", "Blocked in the acceptance tests." ) : $pre; }, 10, 3 );`;
		wp( 'eval', 'delete_option( "afb_test_short_asked" );' );
		wp( 'eval', `${ stub } do_action( "affibloom_amazon_import" );` );
		wp( 'eval', `${ stub } do_action( "affibloom_amazon_import" );` );
		expect( wp( 'eval', 'echo (int) get_option( "afb_test_short_asked", 0 );' ) ).toBe( '1' );
		expect( amazonProducts( code ) ).toBe( 1 );
		expect( wp( 'eval', `global $wpdb; echo (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}affibloom_link_locations WHERE url = %s AND product_id > 0", "${ short }" ) );` ) ).toBe( '1' );
		expect( wp( 'post', 'get', id, '--field=post_content' ) ).toBe( content );
	} );

	// AC5 (AM3; R22, R23, D17, D18).
	test( 'AC5 the switch asks for a tracking ID, turns Multiple IDs on, offers a backup, and Save starts the import', async ( { page, context } ) => {
		setSettings( { auto_monetize_amazon: false, multiple_tracking_ids: false, tracking_id: 'vbvault-20' } );
		await page.goto( '/wp-admin/admin.php?page=affibloom-settings&tab=amazon' );
		const toggle = page.getByLabel( 'Enable Amazon Auto-Monetization' );
		const multiple = page.getByLabel( 'Allow Multiple Tracking IDs' );
		const tag = page.getByLabel( 'Tracking ID for This Site' );
		const dialog = page.getByRole( 'dialog', { name: 'Enable Auto-Amazon' } );

		await tag.fill( '' );
		await toggle.check( { force: true } );
		await expect( page.getByText( 'Tracking ID must be set to use Auto-Amazon.' ) ).toBeVisible();
		await expect( toggle ).not.toBeChecked();

		await tag.fill( 'vbvault-20' );
		await toggle.focus();
		await page.keyboard.press( 'Space' );
		await expect( dialog ).toContainText( 'We recommend creating a backup first.' );
		await expect( multiple ).toBeChecked();
		const guide = context.waitForEvent( 'page' );
		await dialog.getByRole( 'button', { name: 'Create a Backup First' } ).click();
		expect( ( await guide ).url() ).toContain( 'backup' );
		await expect( toggle ).not.toBeChecked();
		await expect( multiple ).not.toBeChecked();

		await toggle.check( { force: true } );
		await dialog.getByRole( 'button', { name: 'Enable' } ).click();
		await expect( toggle ).toBeChecked();
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.getByText( 'Settings saved.' ) ).toBeVisible();
		expect( wp( 'eval', "echo wp_next_scheduled( 'affibloom_amazon_import' ) <= time() + 5 ? 'now' : 'later';" ) ).toBe( 'now' );
		setSettings( { auto_monetize_amazon: false } );
		expect( wp( 'eval', "echo wp_next_scheduled( 'affibloom_amazon_import' ) ? 'yes' : 'no';" ) ).toBe( 'no' );
	} );

	// AC6 (AM4; R29-R31, R35, D19): a seeded queue.
	test( 'AC6 the import notice shows its counts on every Affibloom page, and × hides it until they change', async ( { page } ) => {
		newPostWith( `<p><a href="https://www.amazon.com/dp/${ asin() }">queued</a></p>` );
		wp( 'eval', 'delete_user_meta( 1, "affibloom_amazon_import_dismissed" );' );
		await page.goto( '/wp-admin/admin.php?page=affibloom-groups' );
		const notice = page.locator( '[data-amazon-import-notice]' );
		await expect( notice ).toContainText( 'Amazon import in progress' );
		await expect( notice ).toContainText( /in Dashboard · ~[\d,]+ unique Amazon products still queued/ );
		await expect( notice ).toContainText( /placements indexed/ );

		await notice.getByRole( 'button', { name: 'Dismiss' } ).click();
		await expect( notice ).toHaveCount( 0 );
		await page.reload();
		await expect( page.locator( '[data-amazon-import-notice]' ) ).toHaveCount( 0 );

		newPostWith( `<p><a href="https://www.amazon.com/dp/${ asin() }">one more</a></p>` );
		await page.reload();
		await expect( page.locator( '[data-amazon-import-notice]' ) ).toBeVisible();
	} );

	test( 'AC8 with Auto-Monetization off the event is not scheduled and a run does nothing', () => {
		setSettings( { auto_monetize_amazon: false } );
		expect( wp( 'eval', "echo wp_next_scheduled( 'affibloom_amazon_import' ) ? 'yes' : 'no';" ) ).toBe( 'no' );
		const code = asin();
		newPostWith( `<p><a href="https://www.amazon.com/dp/${ code }">this</a></p>` );
		wp( 'eval', "do_action( 'affibloom_amazon_import' );" );
		expect( amazonProducts( code ) ).toBe( 0 );
	} );
} );
