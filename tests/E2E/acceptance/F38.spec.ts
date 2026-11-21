import { copyFileSync, existsSync, mkdirSync, rmdirSync, unlinkSync } from 'node:fs';
import { join } from 'node:path';
import { test, expect, type Page } from '@playwright/test';
import { blockTrackers, createProduct, unique, wp } from '../utils';

// F38 Admin shell (docs/features/F38/requirements.md). Task ON1 owns AC1-AC3 (header, WordPress menu, titles, footer);
// ON2 adds AC4-AC6. Lasso's rules: the header lists Dashboard, Opportunities, Tables, Groups (each once its screen
// exists), then Affibloom's Settings; the tab of the page's group is current; below 992 px a Menu button folds the
// tabs. The WordPress menu sits after Pages (position 20) with Lasso's submenu order; detail pages are not listed but
// highlight their list's item. Every page ends with "Version <x.y.z>". Other plugins' notices are removed on Affibloom's
// pages; Affibloom's own go to one region under the header (PHP providers, and affibloomNotice() for scripts).

const DASHBOARD = '/wp-admin/admin.php?page=affibloom';

const tabs = ( page: Page ) => page.locator( '.affibloom-header__nav' ).getByRole( 'link' );
const current = ( page: Page ) => page.locator( '.affibloom-header__nav [aria-current="page"]' );
const submenu = ( page: Page ) => page.locator( '#toplevel_page_affibloom .wp-submenu a' );

test.describe( 'F38 Admin shell', () => {
	test.beforeEach( async ( { context } ) => {
		await blockTrackers( context );
	} );

	test( 'AC1 the header lists the screens in Lasso\'s order and marks the page\'s group', async ( { page } ) => {
		const product = createProduct( { name: unique( 'F38 tab' ) } );
		const group = Number( wp( 'term', 'create', 'affibloom_group', unique( 'F38 group' ), '--porcelain' ).trim() );
		try {
			await page.goto( DASHBOARD );
			// Lasso's tabs, then Fields (deviation 152) and Settings; no Analytics or Marketplace.
			await expect( tabs( page ) ).toHaveText( [ 'Dashboard', 'Opportunities', 'Tables', 'Groups', 'Fields', 'Settings' ] );
			await expect( current( page ) ).toHaveText( 'Dashboard' );

			const pages: Array< [ string, string ] > = [
				[ `/wp-admin/admin.php?page=affibloom-product&id=${ product }`, 'Dashboard' ],
				[ '/wp-admin/admin.php?page=affibloom-groups', 'Groups' ],
				[ `/wp-admin/admin.php?page=affibloom-group&id=${ group }`, 'Groups' ],
				[ '/wp-admin/admin.php?page=affibloom-settings', 'Settings' ],
				[ '/wp-admin/admin.php?page=affibloom-settings&tab=display', 'Settings' ],
				[ '/wp-admin/admin.php?page=affibloom-settings&tab=amazon', 'Settings' ],
			];
			for ( const [ url, tab ] of pages ) {
				await page.goto( url );
				await expect( current( page ), url ).toHaveText( tab );
			}

			// Fields has its own tab (deviation 152), current on the list and on a field.
			await page.goto( '/wp-admin/admin.php?page=affibloom-fields' );
			await expect( current( page ) ).toHaveText( 'Fields' );
			const field = new URL( await page.locator( 'a[href*="page=affibloom-field&"]' ).first().getAttribute( 'href' ) ?? '', page.url() );
			await page.goto( field.pathname + field.search );
			await expect( current( page ) ).toHaveText( 'Fields' );
		} finally {
			wp( 'post', 'delete', String( product ), '--force' );
			wp( 'term', 'delete', 'affibloom_group', String( group ) );
		}
	} );

	test( 'AC1 below 992 px the tabs fold behind a Menu button', async ( { page } ) => {
		await page.setViewportSize( { width: 375, height: 800 } );
		await page.goto( DASHBOARD );

		const button = page.getByRole( 'button', { name: 'Menu', exact: true } );
		await expect( button ).toBeVisible();
		await expect( button ).toHaveAttribute( 'aria-expanded', 'false' );
		await expect( tabs( page ).first() ).toBeHidden();

		await button.click();
		await expect( button ).toHaveAttribute( 'aria-expanded', 'true' );
		for ( const name of [ 'Dashboard', 'Groups', 'Fields', 'Settings' ] ) {
			await expect( page.locator( '.affibloom-header__nav' ).getByRole( 'link', { name } ) ).toBeVisible();
		}
		// The page does not scroll sideways with the tabs open.
		expect( await page.evaluate( () => document.documentElement.scrollWidth ) ).toBeLessThanOrEqual( 375 );

		// Escape folds them again and gives the focus back to the button.
		await page.keyboard.press( 'Escape' );
		await expect( button ).toHaveAttribute( 'aria-expanded', 'false' );
		await expect( button ).toBeFocused();

		// At 1280 px there is no Menu button and the tabs show.
		await page.setViewportSize( { width: 1280, height: 900 } );
		await expect( button ).toBeHidden();
		await expect( tabs( page ).first() ).toBeVisible();
	} );

	test( 'AC2 the WordPress menu sits after Pages in Lasso\'s order and highlights detail pages', async ( { page } ) => {
		const product = createProduct( { name: unique( 'F38 menu' ) } );
		try {
			await page.goto( DASHBOARD );
			const order = await page.locator( '#adminmenu > li' ).evaluateAll( ( items ) => items.map( ( li ) => li.id ) );
			expect( order.indexOf( 'toplevel_page_affibloom' ) ).toBe( order.indexOf( 'menu-pages' ) + 1 );

			await expect( submenu( page ) ).toHaveText( [ 'Dashboard', 'Groups', 'Fields', 'Settings' ] );
			await expect( page ).toHaveTitle( /^Dashboard ‹ / );

			// A product page is reachable, not listed, and highlights Dashboard; its title is Lasso's "Link Details".
			await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ product }` );
			await expect( submenu( page ) ).toHaveText( [ 'Dashboard', 'Groups', 'Fields', 'Settings' ] );
			await expect( page.locator( '#toplevel_page_affibloom' ) ).toHaveClass( /wp-has-current-submenu/ );
			await expect( page.locator( '#toplevel_page_affibloom .wp-submenu li.current a' ) ).toHaveText( 'Dashboard' );
			await expect( page ).toHaveTitle( /^Link Details ‹ / );

			const highlights: Array< [ string, string, RegExp ] > = [
				[ '/wp-admin/admin.php?page=affibloom-groups', 'Groups', /^Groups ‹ / ],
				[ '/wp-admin/admin.php?page=affibloom-group', 'Groups', /‹ / ],
				[ '/wp-admin/admin.php?page=affibloom-fields', 'Fields', /^Fields ‹ / ],
				[ '/wp-admin/admin.php?page=affibloom-field', 'Fields', /‹ / ],
				[ '/wp-admin/admin.php?page=affibloom-settings&tab=amazon', 'Settings', /^Settings ‹ / ],
			];
			for ( const [ url, item, title ] of highlights ) {
				await page.goto( url );
				await expect( page.locator( '#toplevel_page_affibloom .wp-submenu li.current a' ), url ).toHaveText( item );
				await expect( page, url ).toHaveTitle( title );
			}
		} finally {
			wp( 'post', 'delete', String( product ), '--force' );
		}
	} );

	test( 'AC3 every Affibloom page ends with the version', async ( { page } ) => {
		const version = wp( 'plugin', 'get', 'affibloom', '--field=version' ).trim();
		for ( const url of [ DASHBOARD, '/wp-admin/admin.php?page=affibloom-groups', '/wp-admin/admin.php?page=affibloom-fields', '/wp-admin/admin.php?page=affibloom-settings&tab=display' ] ) {
			await page.goto( url );
			await expect( page.locator( '.affibloom-footer' ), url ).toHaveText( `Version ${ version }` );
			// WordPress's own version line is blanked on Affibloom's pages only (N6).
			await expect( page.locator( '#footer-upgrade' ), url ).toHaveText( '' );
		}
		await page.goto( '/wp-admin/index.php' );
		await expect( page.locator( '#footer-upgrade' ) ).not.toHaveText( '' );
	} );

	test( 'AC4 other plugins\' notices are not shown on Affibloom\'s pages, and are on WordPress\'s', async ( { page } ) => {
		// The fixture stands in for another plugin: an admin notice and a line in the admin header.
		const muPlugins = join( __dirname, '..', '..', '..', '..', '..', 'mu-plugins' );
		const made = ! existsSync( muPlugins );
		mkdirSync( muPlugins, { recursive: true } );
		const fixture = join( muPlugins, 'affibloom-e2e-foreign-notice.php' );
		copyFileSync( join( __dirname, '..', 'fixtures', 'foreign-notice.php' ), fixture );
		try {
			await page.goto( '/wp-admin/edit.php' );
			await expect( page.locator( '#affibloom-e2e-foreign-notice' ) ).toBeVisible();
			await expect( page.locator( '#affibloom-e2e-foreign-header' ) ).toHaveCount( 1 );

			for ( const url of [ DASHBOARD, '/wp-admin/admin.php?page=affibloom-groups', '/wp-admin/admin.php?page=affibloom-settings&tab=amazon' ] ) {
				await page.goto( url );
				await expect( page.locator( '.affibloom-header' ), url ).toBeVisible();
				await expect( page.locator( '#affibloom-e2e-foreign-notice' ), url ).toHaveCount( 0 );
				await expect( page.locator( '#affibloom-e2e-foreign-header' ), url ).toHaveCount( 0 );
				// WordPress's own admin bar stays.
				await expect( page.locator( '#wpadminbar' ), url ).toBeVisible();
			}

			await page.goto( '/wp-admin/edit.php' );
			await expect( page.locator( '#affibloom-e2e-foreign-notice' ) ).toBeVisible();
		} finally {
			unlinkSync( fixture );
			if ( made ) {
				rmdirSync( muPlugins );
			}
		}
	} );

	test( 'AC7 Automations shows the running background jobs with their counts, or Lao Tzu when none runs', async ( { page } ) => {
		const button = page.getByRole( 'button', { name: /Automations/ } );
		wp( 'option', 'delete', 'affibloom_link_index_state' );
		wp( 'option', 'delete', 'affibloom_bulk_add' );
		await page.goto( DASHBOARD );
		await page.evaluate( () => window.localStorage.clear() );
		await button.click();
		const panel = page.locator( '#affibloom-jobs' );
		await expect( panel.getByText( 'Automations' ) ).toBeVisible();
		await expect( panel.getByText( 'Silence is a source of great strength - Lao Tzu' ) ).toBeVisible();
		await page.locator( '.affibloom-page__title' ).click();
		await expect( panel ).toBeHidden();

		// A link index rebuild is a job: the icon spins with a count, and the line reads "<name> (<done>/<total>)".
		wp( 'eval', 'do_action( "affibloom_link_index_start" );' );
		try {
			// The last answer is reused for 15 s (R43), so this tab forgets it first. The start spawns WP-Cron, so the count
			// may be past 0 already.
			await page.evaluate( () => window.localStorage.clear() );
			await page.reload();
			await expect( button.locator( '.affibloom-jobs__count' ) ).toHaveText( '1' );
			await expect( button ).toHaveClass( /is-running/ );
			await button.click();
			await expect( panel.getByText( /^Scan all links \(\d+\/\d+\)$/ ) ).toBeVisible();
		} finally {
			wp( 'option', 'delete', 'affibloom_link_index_state' );
			wp( 'eval', 'wp_clear_scheduled_hook( "affibloom_link_index_batch" );' );
		}
	} );

	test( 'AC5 a script adds notices to one region under the header, newest last', async ( { page } ) => {
		await page.goto( '/wp-admin/admin.php?page=affibloom-groups' );
		const region = page.locator( '#affibloom-notices' );
		// The Bulk Amazon sync banner (C1I) may show on any Affibloom page while Amazon products are pending; it is not
		// one of the script's notices.
		const notices = region.locator( '.affibloom-notice:not(.affibloom-bulk-sync)' );
		await expect( notices ).toHaveCount( 0 );

		await page.evaluate( () => {
			const notice = ( window as unknown as { affibloomNotice: ( tone: string, message?: unknown ) => void } ).affibloomNotice;
			notice( 'success', 'Your group saved.' );
			notice( 'warning', 'Please Enter GA Tracking ID Before Enable Click Tracking' );
			notice( 'error' );
			notice( 'error', { code: 'not words' } );
		} );

		// A failure without words of its own gets Lasso's.
		const fallback = 'An unexpected error has occurred please try again later.';
		await expect( notices ).toHaveText( [ 'Your group saved.', 'Please Enter GA Tracking ID Before Enable Click Tracking', fallback, fallback ] );
		await expect( notices.nth( 0 ) ).toHaveClass( /affibloom-notice--success/ );
		await expect( notices.nth( 1 ) ).toHaveClass( /affibloom-notice--warning/ );
		await expect( notices.nth( 2 ) ).toHaveClass( /affibloom-notice--error/ );

		// Under the header and above the page's title.
		const header = await page.locator( '.affibloom-header' ).boundingBox();
		const regionBox = await region.boundingBox();
		const title = await page.getByRole( 'heading', { level: 1, name: 'Groups' } ).boundingBox();
		expect( regionBox!.y ).toBeGreaterThanOrEqual( header!.y + header!.height - 1 );
		expect( regionBox!.y + regionBox!.height ).toBeLessThanOrEqual( title!.y );

		// × closes one.
		await notices.nth( 0 ).getByRole( 'button', { name: 'Dismiss this notice' } ).click();
		await expect( notices ).toHaveCount( 3 );
		await expect( notices.first() ).toHaveText( 'Please Enter GA Tracking ID Before Enable Click Tracking' );
	} );

	test( 'AC8 while the link index is being built, every Affibloom page says so and links to its progress', async ( { page } ) => {
		const notice = page.locator( '.affibloom-notices' ).getByText( "We're rounding up a list of your links." );
		wp( 'option', 'delete', 'affibloom_link_index_state' );
		await page.goto( DASHBOARD );
		await expect( notice ).toHaveCount( 0 );

		wp( 'eval', 'do_action( "affibloom_link_index_start" );' );
		try {
			for ( const url of [ DASHBOARD, '/wp-admin/admin.php?page=affibloom-groups' ] ) {
				await page.goto( url );
				await expect( notice, url ).toBeVisible();
			}
			await page.getByRole( 'link', { name: 'See the progress' } ).click();
			await expect( page.locator( '#affibloom-card-link-index' ) ).toBeVisible();
		} finally {
			wp( 'option', 'delete', 'affibloom_link_index_state' );
			wp( 'eval', 'wp_clear_scheduled_hook( "affibloom_link_index_batch" );' );
		}
	} );

	test( 'AC6 an update of Affibloom is announced on every page with WordPress\'s update link', async ( { page } ) => {
		// Keep the site's own update_plugins transient in a test option and put it back after.
		wp(
			'eval',
			'$t = get_site_transient( "update_plugins" ); update_option( "affibloom_e2e_update_plugins", $t, false );' +
				' if ( ! is_object( $t ) ) { $t = new stdClass(); }' +
				' $t->last_checked = time(); $t->response = isset( $t->response ) ? (array) $t->response : array();' +
				' $t->response["affibloom/affibloom.php"] = (object) array( "slug" => "affibloom", "plugin" => "affibloom/affibloom.php", "new_version" => "9.9.9", "package" => "", "url" => "" );' +
				' set_site_transient( "update_plugins", $t );'
		);
		try {
			for ( const url of [ DASHBOARD, '/wp-admin/admin.php?page=affibloom-fields', '/wp-admin/admin.php?page=affibloom-settings' ] ) {
				await page.goto( url );
				const notice = page.locator( '#affibloom-notices .affibloom-notice--update:not(.affibloom-bulk-sync)' );
				await expect( notice, url ).toHaveText( 'There\'s a new version available. Update now.' );
				await expect( notice.getByRole( 'link', { name: 'Update now' } ), url ).toHaveAttribute(
					'href',
					/update\.php\?action=upgrade-plugin&plugin=affibloom%2Faffibloom\.php&_wpnonce=[0-9a-f]+$/
				);
				// Lasso's has no ×.
				await expect( notice.getByRole( 'button' ), url ).toHaveCount( 0 );
			}
		} finally {
			wp(
				'eval',
				'$t = get_option( "affibloom_e2e_update_plugins" ); delete_option( "affibloom_e2e_update_plugins" );' +
					' if ( is_object( $t ) ) { set_site_transient( "update_plugins", $t ); } else { delete_site_transient( "update_plugins" ); }'
			);
		}

		await page.goto( DASHBOARD );
		await expect( page.locator( '#affibloom-notices .affibloom-notice--update:not(.affibloom-bulk-sync)' ) ).toHaveCount( 0 );
	} );
} );
