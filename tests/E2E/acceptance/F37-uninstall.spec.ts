import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';
import { boxBlock, createProduct, newPostWith, setSettings, unique, wp } from '../utils';

/**
 * F37 AC6, F9 AC16 (Task 38): the General tab's Uninstall card (the opt-in removal and the posts that still use
 * Affibloom), and the Dashboard's export of the whole library. The removal itself runs from uninstall.php when the
 * plugin is deleted: tests/Integration/UninstallTest.php (wp-env) and the by-hand run of Task 38's step 5.
 */
test.describe( 'F37 Uninstall and export', () => {
	test( 'AC6 / F9 AC16 the Uninstall card holds the opt-in, its help, and the posts that use Affibloom', async ( { page } ) => {
		setSettings( { delete_data_on_uninstall: false } );
		const title = unique( 'F37 AC6 post' );
		const id = createProduct( { name: unique( 'F37 AC6' ) } );
		newPostWith( boxBlock( id ), title ); // saving the post indexes it

		await page.goto( '/wp-admin/admin.php?page=affibloom-settings' );
		const card = page.locator( '#affibloom-card-uninstall' );
		await expect( card.getByRole( 'heading', { name: 'Uninstall' } ) ).toBeVisible();
		const toggle = card.getByRole( 'switch', { name: 'Delete all Affibloom data when the plugin is deleted' } );
		await expect( toggle ).not.toBeChecked();
		await expect( card ).toContainText( 'Products, click counts and link checks are removed for good. Export them first.' );
		await expect( card.getByRole( 'heading', { name: 'Posts that still use Affibloom' } ) ).toBeVisible();
		await expect( card.getByRole( 'link', { name: title } ) ).toHaveAttribute( 'href', /post\.php\?post=\d+&action=edit/ );

		// The switch saves like every General setting and comes back on after a reload; then it is put back off.
		await toggle.click();
		await page.locator( '#affibloom-settings-save' ).click();
		await expect( page.getByText( 'Settings saved.' ) ).toBeVisible();
		expect( wp( 'eval', 'echo get_option( "affibloom_settings" )["delete_data_on_uninstall"] ? "on" : "off";' ) ).toBe( 'on' );
		// The save loads the page again by itself (F9 AC17); open it fresh once that is done.
		await page.waitForLoadState( 'load' );
		await page.goto( '/wp-admin/admin.php?page=affibloom-settings' );
		await expect( page.locator( '#affibloom-card-uninstall' ).getByRole( 'switch' ) ).toBeChecked();
		await page.locator( '#affibloom-card-uninstall' ).getByRole( 'switch' ).click();
		await page.locator( '#affibloom-settings-save' ).click();
		await expect( page.getByText( 'Settings saved.' ) ).toBeVisible();
		expect( wp( 'eval', 'echo get_option( "affibloom_settings" )["delete_data_on_uninstall"] ? "on" : "off";' ) ).toBe( 'off' );
	} );

	test( 'Task 38 the Dashboard\'s Export CSV downloads every product with every field, names in UTF-8', async ( { page } ) => {
		const name = unique( 'F37 export Ünïcödé ✓' );
		createProduct( { name } );
		const total = Number( wp( 'eval', 'echo wp_count_posts( "affibloom_product" )->publish;' ) );

		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		const download = page.waitForEvent( 'download' );
		await page.getByRole( 'button', { name: 'Export CSV' } ).click();
		const file = await download;
		expect( file.suggestedFilename() ).toMatch( /^affibloom-products-\d{4}-\d{2}-\d{2}\.csv$/ );
		const csv = readFileSync( await file.path(), 'utf8' );
		const lines = csv.trimEnd().split( '\n' ).filter( ( line ) => /^\d+,/.test( line ) || line.startsWith( 'id,' ) );
		expect( lines[ 0 ] ).toMatch( /^id,name,/ );
		expect( lines.length - 1 ).toBe( total );
		expect( csv ).toContain( name );
		expect( csv ).not.toMatch( /secret|credential/i );
		await expect( page.getByText( `Exported ${ total } products.` ) ).toBeVisible();
	} );
} );
