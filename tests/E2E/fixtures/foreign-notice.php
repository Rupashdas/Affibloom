<?php
/**
 * Plugin Name: Affibloom E2E foreign notice
 * Description: Stands in for another plugin in F38 AC4 (Task ON2). tests/E2E/acceptance/F38.spec.ts copies it into
 * wp-content/mu-plugins for one test and removes it after; it is never installed otherwise.
 *
 * @package Affibloom
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_notices',
	static function () {
		echo '<div class="notice notice-info" id="affibloom-e2e-foreign-notice"><p>A notice from another plugin.</p></div>';
	}
);
add_action(
	'in_admin_header',
	static function () {
		echo '<div id="affibloom-e2e-foreign-header">Another plugin in the header.</div>';
	}
);
