<?php
/**
 * Integration test bootstrap: the WordPress test suite inside wp-env.
 *
 * @package Affibloom
 */

require dirname( __DIR__, 2 ) . '/vendor/autoload.php';

$affibloom_tests_dir = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) : '/wordpress-phpunit';

require_once $affibloom_tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function () {
		require dirname( __DIR__, 2 ) . '/affibloom.php';
	}
);

require $affibloom_tests_dir . '/includes/bootstrap.php';
