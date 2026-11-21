<?php
/**
 * Runs when the plugin is deleted. Removes data only if an administrator opted in (General → Uninstall; Task 38).
 *
 * @package Affibloom
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$affibloom_settings = (array) get_option( 'affibloom_settings', array() );
if ( empty( $affibloom_settings['delete_data_on_uninstall'] ) ) {
	return;
}

require_once __DIR__ . '/vendor/autoload.php';

( new Affibloom\Infrastructure\WordPress\DataEraser( $GLOBALS['wpdb'] ) )->run();
