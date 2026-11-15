<?php
/**
 * Test only: answers Affibloom's Amazon requests with recorded responses, so acceptance tests never reach Amazon
 * (docs/features/C1/analysis.md, "Tests"). affibloom.php loads this file only on a local site
 * (wp_get_environment_type() is "local"), and even there it does nothing unless the option affibloom_test_amazon_stub
 * (the AFFIBLOOM_TEST_AMAZON_STUB switch) names a scenario:
 *
 * - "ok": tokens are granted (token.json); getItems answers getItems-ok.json, or getItems-empty.json (not found) for an
 *   ASIN starting B0NOTFOUND, getItems-oos.json (out of stock) for one starting B0OUTSTOCK.
 * - "reject": the token request and the catalogue refuse (401), as for wrong or expired credentials.
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
			return $answer( 429, 'error-429.json' );
		}

		// The token hosts (Login with Amazon and Cognito).
		if ( 'creatorsapi.amazon' !== $host ) {
			return 'reject' === $scenario ? $answer( 401, 'token-401.json' ) : $answer( 200, 'token.json' );
		}

		// The catalogue. A token cached before the scenario changed still reaches it, so "reject" refuses here too.
		if ( 'reject' === $scenario ) {
			return $answer( 401, 'error-401.json' );
		}

		$body = json_decode( (string) ( $args['body'] ?? '' ), true );
		$body = is_array( $body ) ? $body : array();
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );

		if ( str_ends_with( $path, '/getItems' ) ) {
			$asin = strtoupper( (string) ( $body['itemIds'][0] ?? '' ) );
			// ASINs starting B0NOTFOUND and B0OUTSTOCK stand for Amazon's other answers; any other is found and in stock.
			$file = array(
				'B0NOTFOUND' => 'getItems-empty.json',
				'B0OUTSTOCK' => 'getItems-oos.json',
			)[ substr( $asin, 0, 10 ) ] ?? 'getItems-ok.json';

			return $answer(
				200,
				$file,
				array(
					'{{ASIN}}'        => $asin,
					'{{MARKETPLACE}}' => (string) ( $body['marketplace'] ?? 'www.amazon.com' ),
				)
			);
		}

		return $answer( 404, 'error-401.json' );
	},
	10,
	3
);
