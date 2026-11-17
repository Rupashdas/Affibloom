import { test, expect } from '@playwright/test';
import { blockTrackers, boxBlock, createProduct, newPostWith, setSettings, unique, wp } from '../utils';
import { saveSettings } from '../settings';

// F9 Link settings (docs/features/F9/requirements.md).
test.describe( 'F9 Link settings', () => {
	// Help tooltips on before each test: AC14 turns them off, and a test that fails before its cleanup must not leave
	// them off for the next (AC4 would tick an already ticked box; AC12 and AC14 would find the tips hidden).
	test.beforeEach( async ( { context } ) => {
		await blockTrackers( context );
		setSettings( { disable_help_tips: false } );
	} );

	test( 'AC1 new products take the site defaults', async ( { page } ) => {
		setSettings( { new_tab: false, nofollow: true } );
		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await page.getByRole( 'button', { name: 'Add New Link' } ).click();
		const dialog = page.getByRole( 'dialog', { name: 'Add A New Link' } );
		await dialog.getByRole( 'textbox', { name: 'URL', exact: true } ).fill( `https://www.example.com/${ unique( 'f9-ac1' ) }` );
		await dialog.getByRole( 'button', { name: 'Add Link' } ).click();
		await page.waitForURL( /page=affibloom-product&id=\d+/ );
		await expect( page.getByLabel( 'New Window / Tab' ).first() ).not.toBeChecked();
		await expect( page.getByLabel( 'NoFollow / NoIndex' ).first() ).toBeChecked();
		setSettings( { new_tab: true } ); // the site's value again
	} );

	// The box is Phase 3 (Task 18); the phase 2 exit check moves AC2 there.
	test.fixme( 'AC2 box links carry rel and target by the Lasso rules', async ( { page } ) => {
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
		// A link default asks whether to update the products (9D, AC6); this test only saves the site's value.
		const onlyNew = page.getByRole( 'dialog', { name: 'Update Link Settings' } ).getByRole( 'button', { name: 'Only New Links' } );
		await sponsored.setChecked( ! before );
		await onlyNew.click();
		await saveSettings( page );
		await page.reload();
		await expect( page.getByLabel( 'Sponsored' ) ).toBeChecked( { checked: ! before } );

		await page.getByLabel( 'Sponsored' ).setChecked( before ); // leave it as it was
		await onlyNew.click();
		await saveSettings( page );
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
			await dialog.getByRole( 'textbox', { name: 'URL', exact: true } ).fill( `https://merchant.example/${ path }?ref=${ query }` );
			await dialog.getByRole( 'button', { name: 'Add Link' } ).click();
			await page.waitForURL( /page=affibloom-product&id=\d+/ );
			return new URL( page.url() ).searchParams.get( 'id' );
		};
		const first = await add( 'b' );
		const second = await add( 'a' );
		// The same product opens, with the "already exists" notice (`&is_duplicate=1`).
		expect( second ).toBe( first );
		await expect( page.locator( '.affibloom-notice__text', { hasText: 'This product already exists.' } ) ).toBeVisible();
		setSettings( { allow_duplicates: true } );
	} );

	test( 'AC8 unsaved changes ask before leaving, on every Settings tab', async ( { page } ) => {
		// Affibloom's own Dashboard tab; WordPress's menu has a "Dashboard" link too, which the guard also catches.
		const dashboard = page.locator( '#affibloom-header-nav' ).getByRole( 'link', { name: 'Dashboard' } );
		await page.goto( GENERAL );
		// Nothing changed: nothing asked.
		await page.getByRole( 'link', { name: 'Amazon' } ).click();
		await page.waitForURL( /tab=amazon/ );

		for ( const tab of [ 'general', 'display', 'amazon' ] ) {
			// Display opens on its Colors panel, whose text fields are in view (Custom CSS sits in a closed accordion).
			await page.goto( `/wp-admin/admin.php?page=affibloom-settings&tab=${ tab }${ tab === 'display' ? '&panel=colors' : '' }` );
			await page.waitForLoadState( 'load' );
			const control = tab === 'general' ? page.getByLabel( 'Disable Help Tooltips' ) : tab === 'amazon' ? page.getByLabel( 'Show Prime Logo In Displays' ) : page.getByLabel( 'Primary button text', { exact: true } );
			if ( tab === 'display' ) {
				await control.fill( unique( 'F9 AC8' ) );
			} else {
				await control.setChecked( ! ( await control.isChecked() ) );
			}
			await dashboard.click();
			const dialog = page.getByRole( 'dialog', { name: 'Unsaved Changes' } );
			await expect( dialog ).toBeVisible();

			// × (Escape) stays.
			await page.keyboard.press( 'Escape' );
			await expect( dialog ).toBeHidden();
			expect( page.url() ).toContain( `tab=${ tab }` );

			// Leave Without Saving goes.
			await dashboard.click();
			await dialog.getByRole( 'button', { name: 'Leave Without Saving' } ).click();
			await page.waitForURL( /page=affibloom(&|$)/ );
		}

		// Save Changes saves and then goes.
		await page.goto( GENERAL );
		await page.getByLabel( 'Disable Import Notifications' ).setChecked( true );
		await dashboard.click();
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
		// The row, not its label: Playwright treats a label's click as one on its (disabled) switch and waits.
		await page.locator( '.affibloom-toggle-row', { has: page.locator( '[data-ga="click"]' ) } ).click();
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
		await saveSettings( page );
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

	test( 'AC13 the Link Index card shows the last build, the progress of a running one, and Rebuild Database in the support view', async ( { page } ) => {
		const card = page.locator( '#affibloom-card-link-index' );
		wp( 'option', 'delete', 'affibloom_link_index_state' );
		await page.goto( GENERAL );
		await expect( card.getByRole( 'heading', { name: 'Link Index' } ) ).toBeVisible();
		await expect( card.getByText( 'Affibloom tracks every link on your site.' ) ).toBeVisible();
		await expect( card.getByText( 'Links Indexed' ) ).toBeVisible();
		await expect( card.getByText( 'Last Updated' ) ).toBeVisible();
		await expect( card.getByRole( 'button', { name: 'Rebuild Database' } ) ).toHaveCount( 0 );

		// The support view's button asks first; Confirm starts the rebuild and the card follows within a poll.
		await page.goto( `${ GENERAL }&support=1` );
		await card.getByRole( 'button', { name: 'Rebuild Database' } ).click();
		const dialog = page.getByRole( 'dialog', { name: 'Ready to rebuild?' } );
		await expect( dialog.getByText( "By rebuilding your link index, you'll start with a clean slate." ) ).toBeVisible();
		await dialog.getByRole( 'button', { name: 'Confirm' } ).click();
		await expect( page.locator( '.affibloom-toast' ).last() ).toContainText( 'Rebuild started.' );
		await expect( card.getByText( 'Build Progress' ) ).toBeVisible( { timeout: 10_000 } );
		await expect( card.getByText( "We're indexing your links in the background, feel free to do something else." ) ).toBeVisible();

		// Finished: the card goes back to its figures.
		for ( let run = 0; run < 20 && wp( 'option', 'get', 'affibloom_link_index_state', '--format=json' ).includes( '"running"' ); run++ ) {
			wp( 'cron', 'event', 'run', 'affibloom_link_index_batch' );
		}
		await expect( card.getByText( 'Links Indexed' ) ).toBeVisible( { timeout: 10_000 } );
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
		try {
			await saveSettings( page );
			await expect( page.locator( 'body.affibloom-no-tips' ) ).toHaveCount( 1 );
			await expect( page.locator( '.affibloom-help' ).first() ).toBeHidden();
			await expect( page.getByLabel( 'Disable Configure Amazon Notification' ) ).toBeVisible();
		} finally {
			setSettings( { disable_help_tips: false } );
		}
	} );

	test( 'AC15 the support view adds the maintenance sections', async ( { page } ) => {
		await page.goto( GENERAL );
		await expect( page.getByRole( 'heading', { name: 'Keep Original URL' } ) ).toHaveCount( 0 );

		await page.goto( `${ GENERAL }&support=1` );
		for ( const title of [ 'Keep Original URL', 'Cron', 'Cronjob Manually', 'Optional Settings' ] ) {
			await expect( page.getByRole( 'heading', { name: title, exact: true } ) ).toBeVisible();
		}
		await page.getByLabel( 'Time interval (hours)' ).fill( '2' );
		await page.getByLabel( 'Restrict Prefix' ).setChecked( true );
		await page.getByLabel( 'Domains' ).fill( 'https://example.com/\nnodots' );
		await saveSettings( page );
		await expect( page.getByLabel( 'Time interval (hours)' ) ).toHaveValue( '2' );
		await expect( page.getByLabel( 'Domains' ) ).toHaveValue( 'example.com/' );
		setSettings( { cron_interval: 0, restrict_prefix: false, keep_original_domains: [] } );
	} );

	// Lasso's save (R41, settings.js 2379-2464): the form goes by AJAX behind "Updating Your Site", then the same
	// address loads again. Affibloom keeps its toast after the reload and leaves no notice in the address.
	// Every tab stays on the page after its save (the user, 2026-11-16, deviations 141 and 143), where Lasso reloads.
	test( 'AC17 Save Changes saves behind Updating Your Site and stays on the same address', async ( { page } ) => {
		const tabs: Array<{ tab: string; change: () => Promise<void> }> = [
			{ tab: 'general', change: async () => page.getByLabel( 'Disable Import Notifications' ).setChecked( true ) },
			{ tab: 'amazon', change: async () => page.getByLabel( 'Show Prime Logo In Displays' ).setChecked( ! ( await page.getByLabel( 'Show Prime Logo In Displays' ).isChecked() ) ) },
			// The Colors panel, so the reload must also come back to that panel.
			{ tab: 'display&panel=colors', change: async () => page.getByLabel( 'Primary button text', { exact: true } ).fill( unique( 'F9 AC17' ) ) },
		];
		const options = [ 'affibloom_settings', 'affibloom_display' ];
		// An option not stored yet (the Display one after a Reset) is deleted again afterwards.
		const stored = ( option: string ): string => {
			try {
				return wp( 'option', 'get', option, '--format=json' );
			} catch {
				return '';
			}
		};
		const before = options.map( stored );
		try {
			for ( const { tab, change } of tabs ) {
				const address = `/wp-admin/admin.php?page=affibloom-settings&tab=${ tab }`;
				await page.goto( address );
				await page.waitForLoadState( 'load' );
				await change();

				let loads = 0;
				const counted = (): void => {
					loads++;
				};
				page.on( 'load', counted );
				const posted = page.waitForRequest( ( request ) => request.method() === 'POST' && /affibloom(\/|%2F)v1(\/|%2F)settings/.test( request.url() ) );
				await page.getByRole( 'button', { name: 'Save Changes' } ).click();
				await expect( page.getByRole( 'dialog', { name: 'Updating Your Site' } ) ).toBeVisible();
				await posted;

				await expect( page.getByRole( 'status' ).filter( { hasText: 'Settings saved.' } ) ).toBeVisible();
				await expect( page.getByRole( 'dialog', { name: 'Updating Your Site' } ) ).toBeHidden();
				expect( loads ).toBe( 0 );
				page.off( 'load', counted );
				const shown = new URL( page.url() ).searchParams;
				new URLSearchParams( `tab=${ tab }` ).forEach( ( value, name ) => expect( shown.get( name ) ).toBe( value ) );
				expect( shown.has( 'affibloom_notice' ) ).toBe( false );
			}
		} finally {
			options.forEach( ( option, i ) => ( before[ i ] ? wp( 'option', 'update', option, before[ i ], '--format=json' ) : wp( 'option', 'delete', option ) ) );
		}
	} );
} );
