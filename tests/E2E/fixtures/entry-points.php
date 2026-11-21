<?php
/**
 * Prints, as JSON, Affibloom's entry points as the running site registers them (Task 37A): every REST route of the
 * affibloom/v1 namespace with its methods and arguments (hidden routes too, which the REST index leaves out), every
 * admin-post and admin-ajax hook whose name holds "affibloom", and the slugs of Affibloom's admin pages.
 *
 * Run: bash tools/local-wp.sh eval-file tests/E2E/fixtures/entry-points.php
 *
 * @package Affibloom
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- a WP-CLI script, not plugin code.

$routes = array();
foreach ( rest_get_server()->get_routes() as $route => $handlers ) {
	if ( 0 !== strpos( $route, '/affibloom/v1/' ) ) {
		continue;
	}
	foreach ( $handlers as $handler ) {
		$args = array();
		foreach ( (array) ( $handler['args'] ?? array() ) as $name => $arg ) {
			$args[ $name ] = array_intersect_key( (array) $arg, array_flip( array( 'type', 'enum', 'default', 'required', 'minimum', 'items', 'minItems', 'format' ) ) );
		}
		$routes[] = array(
			'route'   => $route,
			'methods' => array_keys( $handler['methods'] ),
			'args'    => (object) $args,
		);
	}
}

// The admin menu is built only in wp-admin: build it here as an administrator to read the pages' slugs.
$admins = get_users(
	array(
		'role'   => 'administrator',
		'number' => 1,
		'fields' => 'ID',
	)
);
wp_set_current_user( (int) ( $admins[0] ?? 0 ) );
require_once ABSPATH . 'wp-admin/includes/admin.php';
do_action( 'admin_menu' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress's own hook, run to build its menu.
$admin_pages = array();
foreach ( (array) ( $GLOBALS['submenu']['affibloom'] ?? array() ) as $item ) {
	$admin_pages[] = $item[2];
}

$hooks = array();
foreach ( array_keys( $GLOBALS['wp_filter'] ) as $hook ) {
	if ( preg_match( '/^(admin_post|admin_post_nopriv|wp_ajax|wp_ajax_nopriv)_/', $hook ) && false !== strpos( $hook, 'affibloom' ) ) {
		$hooks[] = $hook;
	}
}

echo wp_json_encode(
	array(
		'routes' => $routes,
		'pages'  => $admin_pages,
		'hooks'  => $hooks,
	)
);
