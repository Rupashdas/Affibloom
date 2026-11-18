<?php
/**
 * Test only: answers /wp-json/affibloom-stub/v1/status/<code> with that HTTP status, and lets the link checker reach
 * the site itself, so the F11 acceptance tests can point products at dead and live links without leaving the machine.
 * affibloom.php loads this file only on a local site (wp_get_environment_type() is "local"); it never ships.
 *
 * @package Affibloom
 */

defined( 'ABSPATH' ) || exit;

// wp_safe_remote_get() refuses hosts that resolve to this machine; the site's own host and localhost are let through.
add_filter(
	'http_request_host_is_external',
	static function ( $external, $host ) {
		return in_array( $host, array( 'localhost', (string) wp_parse_url( home_url(), PHP_URL_HOST ) ), true ) ? true : $external;
	},
	10,
	2
);

add_action(
	'rest_api_init',
	static function (): void {
		register_rest_route(
			'affibloom-stub/v1',
			'/status/(?P<code>\d{3})',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => static fn( WP_REST_Request $request ) => new WP_REST_Response( array( 'stub' => true ), (int) $request['code'] ),
			)
		);
	}
);
