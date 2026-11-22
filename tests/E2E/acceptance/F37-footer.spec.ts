import { test, expect } from '@playwright/test';
import { wp } from '../utils';

/**
 * F37 AC9, F38 R16 / N6 (Task TL7): every Affibloom screen's footer reads "Version <x.y.z>", and WordPress's own version
 * line is blank there; the rest of wp-admin keeps it. ON1 built both (AdminMenu::update_footer, partials/footer.php);
 * this is their test, the hidden Logs and DB Status pages included.
 */
test( 'AC9 the plugin version is in every Affibloom footer and WordPress\'s is not', async ( { page } ) => {
	test.setTimeout( 180_000 ); // Nine screens; Local's slow worker can hold one ~16 s.
	const version = wp( 'eval', 'echo AFFIBLOOM_VERSION;' ).trim();
	const product = wp( 'eval', 'echo get_posts( array( "post_type" => "affibloom_product", "post_status" => "publish", "numberposts" => 1, "fields" => "ids" ) )[0];' ).trim();
	for ( const screen of [ 'affibloom', `affibloom-product&id=${ product }`, 'affibloom-groups', 'affibloom-fields', 'affibloom-tables', 'affibloom-opportunities', 'affibloom-settings', 'affibloom-logs', 'affibloom-db-status' ] ) {
		await page.goto( `/wp-admin/admin.php?page=${ screen }` );
		await expect( page.locator( '.affibloom-footer' ), screen ).toHaveText( `Version ${ version }` );
		await expect( page.locator( '#footer-upgrade' ), screen ).toHaveText( '' );
	}

	// Elsewhere Affibloom leaves WordPress's line as it is. This site blanks it on every screen with another plugin (seen
	// with Affibloom off), so the Posts screen cannot show it here: the filter chain is asked directly, as wp-admin runs
	// it off an Affibloom page.
	expect( wp( 'eval', 'wp_set_current_user( 1 ); require_once ABSPATH . "wp-admin/includes/admin.php"; unset( $_GET["page"] ); echo apply_filters( "update_footer", "" );' ).trim() ).toMatch( /^Version \d/ );
} );
