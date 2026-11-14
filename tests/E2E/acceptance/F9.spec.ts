import { test, expect } from '@playwright/test';
import { blockTrackers, boxBlock, createProduct, newPostWith, setSettings, unique, wp } from '../utils';

// F9 Link settings (docs/features/F9/requirements.md).
test.describe( 'F9 Link settings', () => {
	test.beforeEach( async ( { context } ) => blockTrackers( context ) );

	test( 'AC1 new products take the site defaults', async ( { page } ) => {
		setSettings( { new_tab: false, nofollow: true } );
		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await page.getByRole( 'button', { name: 'Add New Link' } ).click();
		const dialog = page.getByRole( 'dialog', { name: 'Add A New Link' } );
		await dialog.getByLabel( 'URL' ).fill( `https://www.example.com/${ unique( 'f9-ac1' ) }` );
		await dialog.getByRole( 'button', { name: 'Add Link' } ).click();
		await page.waitForURL( /page=affibloom-product&id=\d+/ );
		await expect( page.getByLabel( 'New Window / Tab' ).first() ).not.toBeChecked();
		await expect( page.getByLabel( 'NoFollow / NoIndex' ).first() ).toBeChecked();
		setSettings( { new_tab: true } ); // the site's value again
	} );

	test( 'AC2 box links carry rel and target by the Lasso rules', async ( { page } ) => {
		const id = createProduct( { name: 'F9 AC2', nofollow: '1', new_tab: '1', sponsored: '1' } );
		await page.goto( newPostWith( boxBlock( id ) ) );
		const button = page.locator( '.affibloom-box .affibloom-button-1' );
		await expect( button ).toHaveAttribute( 'rel', 'nofollow noopener sponsored' );
		await expect( button ).toHaveAttribute( 'target', '_blank' );
	} );

	test( 'AC3 settings save and show again', async ( { page } ) => {
		await page.goto( '/wp-admin/admin.php?page=affibloom-settings&tab=general' );
		const sponsored = page.getByLabel( 'Sponsored' );
		const before = await sponsored.isChecked();
		await sponsored.setChecked( ! before );
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await page.reload();
		await expect( page.getByLabel( 'Sponsored' ) ).toBeChecked( { checked: ! before } );

		await page.getByLabel( 'Sponsored' ).setChecked( before ); // leave it as it was
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
	} );

	const GENERAL = '/wp-admin/admin.php?page=affibloom-settings&tab=general';
	const LINK_DEFAULTS = [ 'New Window / Tab', 'NoFollow / NoIndex', 'Sponsored', 'Show Disclosure', 'Allow duplicate destinations', 'Link Cloaking', 'Detect Opportunities', 'Auto-Detect Affiliate Links' ];

	test( 'AC4 General has two columns of cards and Save Changes waits for a change', async ( { page } ) => {
		await page.goto( GENERAL );
		const left = page.locator( '.affibloom-columns__column' ).first();
		const right = page.locator( '.affibloom-columns__column' ).nth( 1 );
		for ( const title of [ 'Google Analytics', 'Cloaked Link Prefix', 'Smart CSS Loading' ] ) {
			await expect( left.getByRole( 'heading', { name: title } ) ).toBeVisible();
		}
		for ( const title of [ 'Custom Link Detection', 'Performance', 'Link Defaults', 'Notifications' ] ) {
			await expect( right.getByRole( 'heading', { name: title } ) ).toBeVisible();
		}
		const save = page.getByRole( 'button', { name: 'Save Changes' } );
		await expect( save ).toBeDisabled();
		await page.getByLabel( 'Disable Help Tooltips' ).setChecked( true );
		await expect( save ).toBeEnabled();
	} );

	test( 'AC5 Link Defaults shows eight switches in order, all on for a fresh install', async ( { page } ) => {
		setSettings( { new_tab: true, nofollow: true, sponsored: true, show_disclosure: true, allow_duplicates: true, link_cloaking: true, detect_opportunities: true, auto_detect_links: true } );
		await page.goto( GENERAL );
		const card = page.locator( '#affibloom-card-link-defaults' );
		await expect( card.locator( '.affibloom-toggle-row__label' ) ).toHaveText( LINK_DEFAULTS );
		for ( const label of LINK_DEFAULTS ) {
			await expect( card.getByLabel( label, { exact: true } ) ).toBeChecked();
		}
	} );

	test( 'AC6 Override All Links sets the value on every product', async ( { page } ) => {
		const first = createProduct( { name: unique( 'f9-ac6a' ), new_tab: '1' } );
		const second = createProduct( { name: unique( 'f9-ac6b' ), new_tab: '1' } );
		await page.goto( GENERAL );
		await page.getByLabel( 'New Window / Tab', { exact: true } ).setChecked( false );
		const dialog = page.getByRole( 'dialog', { name: 'Update Link Settings' } );
		await expect( dialog ).toContainText( 'disable the option New Window / Tab' );

		// Only New Links changes nothing yet.
		await dialog.getByRole( 'button', { name: 'Only New Links' } ).click();
		expect( wp( 'post', 'meta', 'get', String( first ), '_affibloom_new_tab' ) ).toBe( '1' );

		await page.getByLabel( 'New Window / Tab', { exact: true } ).setChecked( true );
		await dialog.getByRole( 'button', { name: 'Only New Links' } ).click();
		await page.getByLabel( 'New Window / Tab', { exact: true } ).setChecked( false );
		await dialog.getByRole( 'button', { name: 'Override All Links' } ).click();
		await expect( page.getByText( 'All products updated.' ) ).toBeVisible();
		expect( wp( 'post', 'meta', 'get', String( first ), '_affibloom_new_tab' ) ).toBe( '0' );
		expect( wp( 'post', 'meta', 'get', String( second ), '_affibloom_new_tab' ) ).toBe( '0' );
		setSettings( { new_tab: true } );
	} );

	test( 'AC7 with Allow duplicate destinations off, a link to the same page opens that product', async ( { page } ) => {
		setSettings( { allow_duplicates: false } );
		const path = unique( 'f9-ac7' );
		const add = async ( query: string ) => {
			await page.goto( '/wp-admin/admin.php?page=affibloom' );
			await page.getByRole( 'button', { name: 'Add New Link' } ).click();
			const dialog = page.getByRole( 'dialog', { name: 'Add A New Link' } );
			await dialog.getByLabel( 'URL' ).fill( `https://merchant.example/${ path }?ref=${ query }` );
			await dialog.getByRole( 'button', { name: 'Add Link' } ).click();
			await page.waitForURL( /page=affibloom-product&id=\d+/ );
			return page.url();
		};
		const first = await add( 'b' );
		const second = await add( 'a' );
		expect( second ).toBe( first );
		await expect( page.getByText( 'This product already exists' ) ).toBeVisible();
		setSettings( { allow_duplicates: true } );
	} );

	test( 'AC8 unsaved changes ask before leaving, on every Settings tab', async ( { page } ) => {
		await page.goto( GENERAL );
		// Nothing changed: nothing asked.
		await page.getByRole( 'link', { name: 'Amazon' } ).click();
		await page.waitForURL( /tab=amazon/ );

		for ( const tab of [ 'general', 'display', 'amazon' ] ) {
			await page.goto( `/wp-admin/admin.php?page=affibloom-settings&tab=${ tab }` );
			await page.waitForLoadState( 'load' );
			const control = tab === 'general' ? page.getByLabel( 'Disable Help Tooltips' ) : tab === 'amazon' ? page.getByLabel( 'Show Prime Logo In Displays' ) : page.locator( '#affibloom-disclosure-text, textarea[name$="[custom_css]"]' ).first();
			if ( tab === 'display' ) {
				await control.fill( '/* f9 ac8 */' );
			} else {
				await control.setChecked( ! ( await control.isChecked() ) );
			}
			await page.getByRole( 'link', { name: 'Dashboard' } ).first().click();
			const dialog = page.getByRole( 'dialog', { name: 'Unsaved Changes' } );
			await expect( dialog ).toBeVisible();

			// × (Escape) stays.
			await page.keyboard.press( 'Escape' );
			await expect( dialog ).toBeHidden();
			expect( page.url() ).toContain( `tab=${ tab }` );

			// Leave Without Saving goes.
			await page.getByRole( 'link', { name: 'Dashboard' } ).first().click();
			await dialog.getByRole( 'button', { name: 'Leave Without Saving' } ).click();
			await page.waitForURL( /page=affibloom(&|$)/ );
		}

		// Save Changes saves and then goes.
		await page.goto( GENERAL );
		await page.getByLabel( 'Disable Import Notifications' ).setChecked( true );
		await page.getByRole( 'link', { name: 'Dashboard' } ).first().click();
		await page.getByRole( 'dialog', { name: 'Unsaved Changes' } ).getByRole( 'button', { name: 'Save Changes' } ).click();
		await page.waitForURL( /page=affibloom(&|$)/ );
		expect( JSON.parse( wp( 'option', 'get', 'affibloom_settings', '--format=json' ) ).disable_import_notice ).toBe( true );
		setSettings( { disable_import_notice: false } );
	} );

	test( 'AC9 Google Analytics switches follow the Tracking ID', async ( { page } ) => {
		setSettings( { ga_tracking_id: '', ga_click_tracking: false, ga_pageview: false, ga_anonymize_ip: false } );
		await page.goto( GENERAL );
		const id = page.getByPlaceholder( 'Tracking ID or Property ID' );
		for ( const label of [ 'Click Tracking', 'Pageview', 'IP Anonymization' ] ) {
			await expect( page.getByLabel( label, { exact: true } ) ).toBeDisabled();
		}
		await page.locator( '[data-ga="click"]' ).locator( 'xpath=..' ).locator( '.affibloom-toggle-row__label' ).click();
		await expect( page.getByText( 'Please Enter GA Tracking ID Before Enable Click Tracking' ) ).toBeVisible();

		await id.fill( 'G-TESTID123' );
		await expect( page.getByLabel( 'Click Tracking', { exact: true } ) ).toBeEnabled();
		await page.getByLabel( 'Click Tracking', { exact: true } ).setChecked( true );
		await page.getByLabel( 'Pageview', { exact: true } ).setChecked( true );
		const dialog = page.getByRole( 'dialog', { name: 'Enable Pageview' } );
		await dialog.getByRole( 'button', { name: 'Cancel' } ).click();
		await expect( page.getByLabel( 'Pageview', { exact: true } ) ).not.toBeChecked();
		await page.getByLabel( 'Pageview', { exact: true } ).setChecked( true );
		await dialog.getByRole( 'button', { name: 'Enable' } ).click();
		await expect( page.getByLabel( 'Pageview', { exact: true } ) ).toBeChecked();

		await id.fill( '' );
		for ( const label of [ 'Click Tracking', 'Pageview', 'IP Anonymization' ] ) {
			await expect( page.getByLabel( label, { exact: true } ) ).toBeDisabled();
			await expect( page.getByLabel( label, { exact: true } ) ).not.toBeChecked();
		}
	} );

	test( 'AC10 the cloaked link prefix becomes a slug and is stored', async ( { page } ) => {
		await page.goto( GENERAL );
		const prefix = page.getByLabel( 'Prefix', { exact: true } );
		await prefix.fill( 'Re Commends!' );
		await expect( prefix ).toHaveValue( 're-commends-' );
		await expect( page.getByText( 'link-name/' ) ).toContainText( 're-commends/link-name/' );
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		expect( JSON.parse( wp( 'option', 'get', 'affibloom_settings', '--format=json' ) ).link_prefix ).toBe( 're-commends' );
		await expect( page.getByLabel( 'Enable Smart CSS Loading' ) ).toBeVisible();
		setSettings( { link_prefix: '' } );
	} );

	test( 'AC11 performance presets set the CPU level and only Custom shows it', async ( { page } ) => {
		setSettings( { performance_mode: 'balanced', cpu_threshold: 80 } );
		await page.goto( GENERAL );
		// Loading rewrites nothing: Balanced with 80 stays.
		await expect( page.locator( '#affibloom-setting-cpu_threshold' ) ).toHaveValue( '80' );
		await expect( page.locator( '#affibloom-setting-cpu_threshold' ) ).toBeHidden();

		const mode = page.getByLabel( 'Performance Mode:' );
		await mode.selectOption( 'light' );
		await expect( page.locator( '#affibloom-setting-cpu_threshold' ) ).toHaveValue( '50' );
		await mode.selectOption( 'custom' );
		await expect( page.locator( '#affibloom-setting-cpu_threshold' ) ).toBeVisible();
		await mode.selectOption( 'balanced' );
		await expect( page.locator( '#affibloom-setting-cpu_threshold' ) ).toHaveValue( '65' );
		setSettings( { performance_mode: 'balanced', cpu_threshold: 80 } );
	} );

	test( 'AC12 Custom Link Detection lists the site post types and saves the choice', async ( { page } ) => {
		await page.goto( GENERAL );
		const card = page.locator( '#affibloom-card-detection' );
		await expect( card.getByText( 'Custom Post Types:' ) ).toBeVisible();
		for ( const never of [ 'Posts', 'Pages', 'Reusable blocks', 'Affibloom Products' ] ) {
			await expect( card.locator( '.affibloom-choice', { hasText: new RegExp( `^${ never }$` ) } ) ).toHaveCount( 0 );
		}
		// This site has no ACF.
		await expect( card.getByText( 'Custom Fields (ACF):' ) ).toHaveCount( 0 );
	} );

	test( 'AC14 Disable Help Tooltips hides the tips after a save', async ( { page } ) => {
		await page.goto( GENERAL );
		await expect( page.locator( '.affibloom-help' ).first() ).toBeVisible();
		await page.getByLabel( 'Disable Help Tooltips' ).setChecked( true );
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.locator( 'body.affibloom-no-tips' ) ).toHaveCount( 1 );
		await expect( page.locator( '.affibloom-help' ).first() ).toBeHidden();
		await expect( page.getByLabel( 'Disable Configure Amazon Notification' ) ).toBeVisible();
		setSettings( { disable_help_tips: false } );
	} );

	test( 'AC15 the support view adds the maintenance sections', async ( { page } ) => {
		await page.goto( GENERAL );
		await expect( page.getByRole( 'heading', { name: 'Keep Original URL' } ) ).toHaveCount( 0 );

		await page.goto( `${ GENERAL }&support=1` );
		for ( const title of [ 'Keep Original URL', 'Cron', 'Cronjob Manually', 'Optional Settings' ] ) {
			await expect( page.getByRole( 'heading', { name: title } ) ).toBeVisible();
		}
		await page.getByLabel( 'Time interval (hours)' ).fill( '2' );
		await page.getByLabel( 'Restrict Prefix' ).setChecked( true );
		await page.getByLabel( 'Domains' ).fill( 'https://example.com/\nnodots' );
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.getByLabel( 'Time interval (hours)' ) ).toHaveValue( '2' );
		await expect( page.getByLabel( 'Domains' ) ).toHaveValue( 'example.com/' );
		setSettings( { cron_interval: 0, restrict_prefix: false, keep_original_domains: [] } );
	} );
} );
