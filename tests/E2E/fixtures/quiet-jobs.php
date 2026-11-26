<?php
/**
 * Stops Affibloom's background events before a Playwright run (globalSetup, tests/E2E/quiet-jobs.ts), so no Amazon
 * refresh or leftover bulk pass holds one of Local's two PHP workers while the tests load pages (C1Q6b).
 *
 * Usage: wp eval-file tests/E2E/fixtures/quiet-jobs.php
 *
 * The run no longer saves and restores the site (the Local database is dummy data, deviation 129); run-state.php stays
 * as an opt-in tool for a clean-up by hand.
 *
 * @package Affibloom
 */

defined( 'WP_CLI' ) || exit;

// The plugin schedules the hourly refresh again an hour out on the next request; a bulk sync or bulk add event only
// wakes a job that a test starts again itself.
wp_clear_scheduled_hook( 'affibloom_amazon_refresh' );
wp_clear_scheduled_hook( 'affibloom_amazon_refresh_pass' );
wp_clear_scheduled_hook( 'affibloom_amazon_bulk_sync' );
wp_clear_scheduled_hook( 'affibloom_bulk_add' );
WP_CLI::log( 'quiet-jobs: Affibloom background events cleared.' );
// The Dashboard's saved views that tests made (their names end in unique()'s "-<time>-<random>", tests/E2E/utils.ts):
// a test that saves one does not delete it, and at 30 a user can save no more ("Saved view limit reached.").
foreach ( get_users( array( 'fields' => 'ID' ) ) as $affibloom_user ) { // Local has a handful of users.
	$affibloom_views = get_user_meta( (int) $affibloom_user, 'affibloom_saved_views', true );
	if ( ! is_array( $affibloom_views ) ) {
		continue;
	}
	$affibloom_kept = array_values( array_filter( $affibloom_views, static fn ( $view ): bool => ! is_array( $view ) || 1 !== preg_match( '/-[0-9a-z]{8}-[0-9a-z]{1,4}( view)?$/', (string) ( $view['name'] ?? '' ) ) ) );
	if ( count( $affibloom_kept ) !== count( $affibloom_views ) ) {
		update_user_meta( (int) $affibloom_user, 'affibloom_saved_views', $affibloom_kept );
		WP_CLI::log( sprintf( 'quiet-jobs: %d saved views of the tests removed for user %d.', count( $affibloom_views ) - count( $affibloom_kept ), (int) $affibloom_user ) );
	}
}
