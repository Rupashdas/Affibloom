<?php
/**
 * Plugin Name: Affibloom performance measure (test only)
 * Description: Task 37B. Copied into wp-content/mu-plugins by tests/E2E/performance.spec.ts for its run and removed
 * after; never ships. Works with Affibloom active or not (the comparison with Lasso needs both). On a request that
 * sends `X-Affibloom-Measure: 1` it answers with:
 *   X-Affibloom-Queries  the number of database queries of the request (get_num_queries());
 *   X-Affibloom-Time     the PHP time in milliseconds (timer_stop());
 *   X-Affibloom-Http     the outside HTTP requests Affibloom's code started (a request whose call stack passes
 *                        through plugins/affibloom/src and whose host is not the site's).
 * Pages are buffered so the headers can be sent at the end; REST answers and redirects get them on their way out.
 *
 * @package Affibloom
 */

// phpcs:disable WordPress.Security.ValidatedSanitizedInput, WordPress.NamingConventions.PrefixAllGlobals -- a test-only must-use plugin.

defined( 'ABSPATH' ) || exit;

if ( 'local' !== wp_get_environment_type() || empty( $_SERVER['HTTP_X_AFFIBLOOM_MEASURE'] ) ) {
	return;
}

$GLOBALS['affibloom_measure_http'] = 0;

// `X-Affibloom-Measure: log` also writes the request's queries, with their callers, to the plugin's .local/ folder
// (gitignored), to find a query that repeats.
if ( 'log' === $_SERVER['HTTP_X_AFFIBLOOM_MEASURE'] ) {
	defined( 'SAVEQUERIES' ) || define( 'SAVEQUERIES', true );
	add_action(
		'shutdown',
		static function (): void {
			$lines = array();
			foreach ( (array) $GLOBALS['wpdb']->queries as $query ) {
				$lines[] = round( $query[1] * 1000, 1 ) . ' ms  ' . preg_replace( '/\s+/', ' ', $query[0] ) . "\n    " . $query[2];
			}
			file_put_contents( WP_PLUGIN_DIR . '/affibloom/.local/queries.txt', implode( "\n", $lines ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- a test-only log.
		},
		PHP_INT_MAX
	);
}

add_filter(
	'pre_http_request',
	static function ( $pre, $args, $url ) {
		$host = (string) wp_parse_url( $url, PHP_URL_HOST );
		if ( (string) wp_parse_url( home_url(), PHP_URL_HOST ) === $host ) {
			return $pre;
		}
		foreach ( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ) as $frame ) { // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace -- measuring who calls.
			if ( isset( $frame['file'] ) && false !== strpos( str_replace( '\\', '/', $frame['file'] ), 'plugins/affibloom/src/' ) ) {
				++$GLOBALS['affibloom_measure_http'];
				break;
			}
		}
		return $pre;
	},
	1,
	3
);

$affibloom_measure_headers = static function (): void {
	header( 'X-Affibloom-Queries: ' . get_num_queries() );
	header( 'X-Affibloom-Time: ' . round( timer_stop( 0, 6 ) * 1000 ) );
	header( 'X-Affibloom-Http: ' . (int) $GLOBALS['affibloom_measure_http'] );
};

// Pages, the admin's too: the whole output is held, so the headers can still be sent when it is done.
add_action(
	'init',
	static function () use ( $affibloom_measure_headers ): void {
		if ( defined( 'REST_REQUEST' ) || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}
		ob_start(
			static function ( string $html ) use ( $affibloom_measure_headers ): string {
				if ( ! headers_sent() ) {
					$affibloom_measure_headers();
				}
				return $html;
			}
		);
	},
	0
);

add_filter(
	'rest_post_dispatch',
	static function ( $response ) use ( $affibloom_measure_headers ) {
		$affibloom_measure_headers();
		return $response;
	},
	PHP_INT_MAX
);

add_filter(
	'wp_redirect',
	static function ( $location ) use ( $affibloom_measure_headers ) {
		$affibloom_measure_headers();
		return $location;
	},
	PHP_INT_MAX
);
