<?php
/**
 * Plugin Name:       Affibloom
 * Description:       Affiliate product library and product boxes for VolleyballVault.
 * Version:           0.1.0
 * Requires at least: 7.1.4
 * Requires PHP:      8.3
 * Author:            Devrupash
 * Author URI:        https://devrupash.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       affibloom
 * Domain Path:       /languages
 *
 * @package Affibloom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AFFIBLOOM_VERSION', '0.1.0' );
define( 'AFFIBLOOM_FILE', __FILE__ );

/**
 * Explains why the plugin did not start. Shown only when the checks below fail.
 *
 * The return type is a docblock, not `: void`, so this file stays readable by
 * any PHP version and can show the notice instead of a parse error.
 *
 * @return void
 */
function affibloom_requirements_notice() {
	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html__( 'Affibloom needs PHP 8.3 and WordPress 7.1.4 or newer. It has not started.', 'affibloom' )
	);
}

// WordPress checks the header's requirements before activation, but not after
// a server is downgraded, so they are checked again on every load.
if ( version_compare( PHP_VERSION, '8.3', '<' ) || version_compare( get_bloginfo( 'version' ), '7.1.4', '<' ) ) {
	add_action( 'admin_notices', 'affibloom_requirements_notice' );
	return;
}

if ( ! is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	add_action( 'admin_notices', 'affibloom_requirements_notice' );
	return;
}

require __DIR__ . '/vendor/autoload.php';

Affibloom\Plugin::boot( __FILE__ );
