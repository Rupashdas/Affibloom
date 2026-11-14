import { test, expect, type Page } from '@playwright/test';
import { blockTrackers, createProduct, unique, wp } from '../utils';

// F38 Admin shell (docs/features/F38/requirements.md). Task ON1 owns AC1-AC3 (header, WordPress menu, titles, footer);
// ON2 adds AC4-AC6. Lasso's rules: the header lists Dashboard, Opportunities, Tables, Groups (each once its screen
// exists), then Affibloom's Settings; the tab of the page's group is current; below 992 px a Menu button folds the
// tabs. The WordPress menu sits after Pages (position 20) with Lasso's submenu order; detail pages are not listed but
// highlight their list's item. Every page ends with "Version <x.y.z>".

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
			// Opportunities and Tables join between Dashboard and Groups when their screens exist; no Analytics or Marketplace.
			await expect( tabs( page ) ).toHaveText( [ 'Dashboard', 'Groups', 'Settings' ] );
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

			// Fields is a menu item, not a tab (Lasso's header has none): no tab is current there.
			await page.goto( '/wp-admin/admin.php?page=affibloom-fields' );
			await expect( current( page ) ).toHaveCount( 0 );
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
		for ( const name of [ 'Dashboard', 'Groups', 'Settings' ] ) {
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
} );
