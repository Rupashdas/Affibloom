<?php
/**
 * Test only: answers Affibloom's Amazon requests with recorded responses, so acceptance tests never reach Amazon
 * (docs/features/C1/analysis.md, "Tests"). affibloom.php loads this file only on a local site
 * (wp_get_environment_type() is "local"), and even there it does nothing unless the option affibloom_test_amazon_stub
 * (the AFFIBLOOM_TEST_AMAZON_STUB switch) names a scenario:
 *
 * - "ok": tokens are granted (tests/fixtures/amazon/token.json).
 * - "reject": the token request is refused (401), as for wrong credentials.
 * - "unavailable": every request answers 503.
 * - "throttle": every request answers 429.
 *
 * Every request it answers is counted in affibloom_test_amazon_requests, so a test can show that none was made.
 *
 * @package Affibloom
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'pre_http_request',
	static function ( $pre, $args, $url ) {
		$scenario = get_option( 'affibloom_test_amazon_stub', '' );
		$host     = (string) wp_parse_url( $url, PHP_URL_HOST );
		$amazon   = 'creatorsapi.amazon' === $host || str_ends_with( $host, '.amazoncognito.com' )
			|| in_array( $host, array( 'api.amazon.com', 'api.amazon.co.uk', 'api.amazon.co.jp' ), true );
		if ( '' === $scenario || ! $amazon ) {
			return $pre;
		}

		update_option( 'affibloom_test_amazon_requests', (int) get_option( 'affibloom_test_amazon_requests', 0 ) + 1, false );

		$fixtures = WP_PLUGIN_DIR . '/affibloom/tests/fixtures/amazon/';
		$answer   = static function ( int $status, string $file, array $replace = array() ) use ( $fixtures ): array {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a local fixture.
			$body = (string) file_get_contents( $fixtures . $file );
			return array(
				'headers'  => array( 'content-type' => 'application/json' ),
				'body'     => strtr( $body, $replace ),
				'response' => array(
					'code'    => $status,
					'message' => '',
				),
				'cookies'  => array(),
				'filename' => null,
			);
		};

		if ( 'unavailable' === $scenario ) {
			return $answer( 503, 'token-503.json' );
		}
		if ( 'throttle' === $scenario ) {
			return $answer( 429, 'token-503.json' );
		}

		// The token hosts (Login with Amazon and Cognito).
		if ( 'creatorsapi.amazon' !== $host ) {
			return 'reject' === $scenario ? $answer( 401, 'token-401.json' ) : $answer( 200, 'token.json' );
		}

		return $answer( 404, 'token-401.json' );
	},
	10,
	3
);
