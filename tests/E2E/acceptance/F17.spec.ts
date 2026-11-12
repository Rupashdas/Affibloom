import { test, expect, type Page } from '@playwright/test';
import { blockTrackers, setSettings, wp } from '../utils';

// F17 Amazon settings (docs/features/F17/requirements.md).
test.describe( 'F17 Amazon settings', () => {
	test.beforeEach( async ( { context } ) => blockTrackers( context ) );

	// Lasso's factory defaults for the Amazon tab, put back after the suite.
	test.afterAll( () => {
		setSettings( {
			tracking_id: '',
			multiple_tracking_ids: true,
			tracking_id_whitelist: [],
			tracking_id_blacklist: [],
			auto_monetize_amazon: false,
			show_discount_pricing: false,
			days_before_out_of_stock_alerts: 0,
		} );
	} );

	const amazonTab = '/wp-admin/admin.php?page=affibloom-settings&tab=amazon';
	const stored = (): Record< string, any > => JSON.parse( wp( 'option', 'get', 'affibloom_settings', '--format=json' ) );
	const save = ( page: Page ) => page.getByRole( 'button', { name: 'Save Changes' } ).click();

	test( 'AC1 every control shows its value and saves', async ( { page } ) => {
		await page.goto( amazonTab );
		await page.getByLabel( 'Tracking ID for This Site' ).fill( 'vbvault-20' );
		await page.getByLabel( 'Enable Amazon Auto-Monetization' ).check();
		await page.getByLabel( 'Show Discount Pricing' ).check();
		await page.getByLabel( 'Days before out of stock alerts' ).fill( '5' );
		await save( page );

		await expect( page.locator( '.affibloom-toast' ) ).toContainText( 'Settings saved' );
		await expect( page.getByLabel( 'Show Discount Pricing' ) ).toBeChecked();
		const flat = stored();
		expect( flat.tracking_id ).toBe( 'vbvault-20' );
		expect( flat.auto_monetize_amazon ).toBe( true );
		expect( flat.days_before_out_of_stock_alerts ).toBe( 5 );
	} );

	test( 'AC2 an invalid tracking ID is refused and Lasso\'s valid forms are accepted', async ( { page } ) => {
		setSettings( { tracking_id: 'vbvault-20' } );
		await page.goto( amazonTab );
		const field = page.getByLabel( 'Tracking ID for This Site' );

		await field.fill( 'vbvault' );
		await expect( page.locator( '#affibloom-setting-tracking_id-error' ) ).toHaveText( 'This is an invalid Tracking ID' );
		await save( page );
		await expect( page.locator( '.affibloom-toast' ) ).toContainText( 'kept their previous setting' );
		await expect( field ).toHaveValue( 'vbvault-20' );
		await expect( field ).toHaveAttribute( 'aria-invalid', 'true' );

		await field.fill( 'my_tag.us-21' );
		await save( page );
		expect( stored().tracking_id ).toBe( 'my_tag.us-21' );
	} );

	test( 'AC3 the whitelist is a sorted set, refuses an invalid ID, and a removed ID goes to the blacklist', async ( { page } ) => {
		setSettings( { multiple_tracking_ids: true, tracking_id_whitelist: [], tracking_id_blacklist: [] } );
		await page.goto( amazonTab );
		const add = page.getByRole( 'textbox', { name: 'Tracking ID Whitelist' } );

		await add.fill( 'b-20 a-20 b-20 nope' );
		await add.press( 'Enter' );
		await expect( page.locator( '.affibloom-tag' ) ).toHaveText( [ /a-20/, /b-20/ ] );
		await expect( add ).toHaveValue( 'nope' ); // left in the box, marked
		await add.fill( '' );
		await save( page );
		expect( stored().tracking_id_whitelist ).toEqual( [ 'a-20', 'b-20' ] );

		await page.getByRole( 'button', { name: 'Remove a-20' } ).click();
		await save( page );
		expect( stored().tracking_id_whitelist ).toEqual( [ 'b-20' ] );
		expect( stored().tracking_id_blacklist ).toEqual( [ 'a-20' ] );
	} );

	test( 'AC4 the whitelist is disabled while multiple IDs is off, and keeps its value', async ( { page } ) => {
		setSettings( { multiple_tracking_ids: true, tracking_id_whitelist: [ 'b-20' ] } );
		await page.goto( amazonTab );

		await page.getByLabel( 'Allow Multiple Tracking IDs' ).uncheck();
		await expect( page.getByRole( 'textbox', { name: 'Tracking ID Whitelist' } ) ).toBeDisabled();
		await save( page );

		await expect( page.locator( '.affibloom-toast' ) ).toContainText( 'Settings saved' ); // not "kept their previous"
		expect( stored().tracking_id_whitelist ).toEqual( [ 'b-20' ] );
		expect( stored().multiple_tracking_ids ).toBe( false );
	} );

	test( 'AC5 out-of-stock days take 0 to 30', async ( { page } ) => {
		await page.goto( amazonTab );
		const days = page.getByLabel( 'Days before out of stock alerts' );
		await expect( days ).toHaveAttribute( 'min', '0' );
		await expect( days ).toHaveAttribute( 'max', '30' );
	} );
} );
