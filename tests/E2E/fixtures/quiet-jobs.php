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
