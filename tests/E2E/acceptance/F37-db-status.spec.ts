import { test, expect } from '@playwright/test';
import { wp } from '../utils';

/**
 * F37 AC5 (Task TL4): the hidden DB Status page, and the self-repair after an SQL error says a table is missing. The
 * limit (three repairs, then one an hour) is TableRepairTest's (unit).
 */
test.describe( 'F37 DB Status', () => {
	const tables = [ 'link_locations', 'link_issues', 'clicks', 'amazon_products', 'amazon_short_links', 'url_variants', 'detected_links' ];

	test( 'AC5 DB Status lists every Affibloom table with what creating it did', async ( { page } ) => {
		await page.goto( '/wp-admin/admin.php?page=affibloom-db-status' );
		await expect( page.getByRole( 'heading', { name: 'DB Status', level: 1 } ) ).toBeVisible();
		await expect( page.getByRole( 'columnheader', { name: 'Table Name' } ) ).toBeVisible();
		await expect( page.getByRole( 'columnheader', { name: 'Output' } ) ).toBeVisible();
		for ( const table of tables ) {
			const row = page.getByRole( 'row', { name: new RegExp( `affibloom_${ table }\\b` ) } );
			await expect( row ).toContainText( 'Table already exists.' );
		}
	} );

	test( 'AC5 a missing table is created again after the request that hit it, and the error is logged', async ( { page } ) => {
		wp( 'option', 'delete', 'affibloom_repair' );
		wp( 'db', 'query', 'DROP TABLE wp_affibloom_amazon_short_links' );
		// Any query on it fails; at the request's end the error is logged and the tables are repaired.
		wp( 'eval', 'global $wpdb; $wpdb->suppress_errors( true ); $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}affibloom_amazon_short_links" );' );

		expect( wp( 'db', 'query', "SHOW TABLES LIKE 'wp_affibloom_amazon_short_links'", '--skip-column-names' ) ).toBe( 'wp_affibloom_amazon_short_links' );
		await page.goto( '/wp-admin/admin.php?page=affibloom-logs' );
		await expect( page.locator( '#affibloom-log-text-sql_errors' ) ).toHaveValue( /affibloom_amazon_short_links' doesn't exist[\s\S]*Tables repaired/ );
	} );
} );
