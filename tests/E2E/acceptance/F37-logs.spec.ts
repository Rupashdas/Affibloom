import { test, expect } from '@playwright/test';
import { setSettings, unique, wp } from '../utils';

/**
 * F37 AC3, AC4 (Task TL3): the hidden Logs page and its log files. Forced writes, the newest file per type and the
 * 14-day pruning are FileLogTest's (unit).
 */
test.describe( 'F37 Logs', () => {
	const boxes: Array< [ string, string ] > = [
		[ 'Link Discovery', 'Every time a new link on your site is discovered and stored.' ],
		[ 'Amazon Processing', 'Each time your Amazon links are updated.' ],
		[ 'Amazon Product API Errors', 'Any errors that occur when trying to hit the Amazon Product API.' ],
		[ 'Link Alerts', 'Every time a health check finds a problem with a URL.' ],
		[ 'Duplicate Slug Changes', 'Any slug conflicts we detect when changing a permalink.' ],
		[ 'SQL Errors', 'Any database errors that occur.' ],
	];

	test( 'AC3 the Logs page shows Enable Logs and the newest log of each type, read only; Save Changes stores the switch', async ( { page } ) => {
		setSettings( { enable_logs: true } );
		const url = `https://www.example.com/${ unique( 'tl3' ) }`;
		// A link check that finds a problem writes to Link Alerts, through the event the check fires (F11).
		wp( 'eval', `do_action( "affibloom_link_issue_detected", 1, "${ url }", "404" );` );

		await page.goto( '/wp-admin/admin.php?page=affibloom-logs' );
		await expect( page.getByRole( 'heading', { name: 'Logs', level: 1 } ) ).toBeVisible();
		const toggle = page.getByRole( 'switch', { name: 'Enable Logs' } );
		await expect( toggle ).toBeChecked();
		for ( const [ title, description ] of boxes ) {
			const box = page.locator( '.affibloom-log', { has: page.getByRole( 'heading', { name: title, exact: true } ) } );
			await expect( box ).toContainText( description );
			await expect( box.getByRole( 'textbox' ) ).toHaveAttribute( 'readonly', '' );
		}
		await expect( page.locator( '.affibloom-log', { has: page.getByRole( 'heading', { name: 'Link Alerts' } ) } ).getByRole( 'textbox' ) ).toHaveValue( new RegExp( url.replace( /[.*+?^${}()|[\]\\/]/g, '\\$&' ) ) );

		await toggle.click();
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.getByText( 'Settings saved.' ) ).toBeVisible();
		expect( wp( 'eval', 'echo get_option( "affibloom_settings" )["enable_logs"] ? "on" : "off";' ) ).toBe( 'off' );
	} );

	test( 'AC4 a log file is not readable from the web', async ( { request } ) => {
		setSettings( { enable_logs: true } );
		const marker = unique( 'tl3-secret' );
		wp( 'eval', `do_action( "affibloom_link_issue_detected", 1, "https://www.example.com/${ marker }", "404" );` );
		const file = wp( 'eval', 'foreach ( glob( wp_upload_dir()["basedir"] . "/affibloom-logs/*_url_issue_check.log.php" ) as $f ) { echo basename( $f ), "\\n"; }' ).trim().split( '\n' ).pop() as string;
		expect( file ).toMatch( /_url_issue_check\.log\.php$/ );

		const answer = await request.get( `/wp-content/uploads/affibloom-logs/${ file }` );
		expect( await answer.text() ).not.toContain( marker );
		const listing = await request.get( '/wp-content/uploads/affibloom-logs/' );
		expect( await listing.text() ).not.toContain( file );
		setSettings( { enable_logs: false } );
	} );
} );
