<?php
/**
 * Test only: answers Affibloom's Amazon requests with recorded responses, so acceptance tests never reach Amazon
 * (docs/features/C1/analysis.md, "Tests"). affibloom.php loads this file only on a local site
 * (wp_get_environment_type() is "local"), and even there it does nothing unless the option affibloom_test_amazon_stub
 * (the AFFIBLOOM_TEST_AMAZON_STUB switch) names a scenario:
 *
 * - "ok": tokens are granted (token.json); getItems answers getItems-ok.json, or getItems-empty.json (not found) for an
 *   ASIN starting B0NOTFOUND, getItems-oos.json (out of stock) for one starting B0OUTSTOCK; searchItems answers ten
 *   items a page from searchItems.json, three pages (C1M).
 * - "reject": the token request and the catalogue refuse (401), as for wrong or expired credentials.
 * - "unavailable": every request answers 503.
 * - "throttle": every request answers 429.
 *
 * It answers only the suite's own requests (test Credential IDs, its own token); a person's request passes to Amazon.
 * Every request it answers is counted in affibloom_test_amazon_requests, so a test can show that none was made.
 *
 * @package Affibloom
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether a Credential ID is one of the suite's test IDs (C1.spec.ts: "…oa2-client.e2e", "ACCEPTANCE-ID-C1"). The
 * opt-in restore (tests/E2E/fixtures/run-state.php) reads it too.
 *
 * @param string $credential_id A Credential ID.
 */
function affibloom_e2e_is_test_credential( string $credential_id ): bool {
	return str_ends_with( $credential_id, '.e2e' ) || str_starts_with( $credential_id, 'ACCEPTANCE-' );
}

/**
 * Whether an Amazon request comes from the suite: a token request with a test Credential ID, or a catalogue request
 * carrying the token the stub hands out (fixtures/amazon/token.json).
 *
 * @param array<string, mixed> $args The request's arguments.
 * @param string               $host Its host.
 */
function affibloom_e2e_is_stub_request( array $args, string $host ): bool {
	if ( 'creatorsapi.amazon' === $host ) {
		$headers = is_array( $args['headers'] ?? null ) ? $args['headers'] : array();
		return str_contains( (string) ( $headers['Authorization'] ?? '' ), 'Bearer stub-access-token' );
	}
	$body = $args['body'] ?? '';
	$body = is_array( $body ) ? $body : json_decode( (string) $body, true );

	return is_array( $body ) && affibloom_e2e_is_test_credential( (string) ( $body['client_id'] ?? '' ) );
}

add_filter(
	'pre_http_request',
	static function ( $pre, $args, $url ) {
		$scenario = get_option( 'affibloom_test_amazon_stub', '' );
		$host     = (string) wp_parse_url( $url, PHP_URL_HOST );

		// geni.us (C1K): while any scenario is set, https://geni.us/<code> answers 301 to the Amazon product
		// B0<CODE, upper case, padded with X to eight>, where the page fetcher stops (an Amazon store link).
		if ( '' !== $scenario && 'geni.us' === $host ) {
			$code = strtoupper( substr( str_pad( trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' ), 8, 'X' ), 0, 8 ) );
			return array(
				'headers'  => array( 'location' => 'https://www.amazon.com/dp/B0' . $code ),
				'body'     => '',
				'response' => array(
					'code'    => 301,
					'message' => 'Moved Permanently',
				),
				'cookies'  => array(),
				'filename' => null,
			);
		}
		$amazon = 'creatorsapi.amazon' === $host || str_ends_with( $host, '.amazoncognito.com' )
			|| in_array( $host, array( 'api.amazon.com', 'api.amazon.co.uk', 'api.amazon.co.jp' ), true );
		if ( '' === $scenario || ! $amazon ) {
			return $pre;
		}
		// Only the suite's own requests are answered (C1Q0, deviation 119): a token request for one of its test Credential
		// IDs, or a catalogue request with the stub's token. A person's Validate during a run reaches Amazon.
		if ( ! affibloom_e2e_is_stub_request( $args, $host ) ) {
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
			// Each ASIN asked (up to ten, C1Q6b) gets its own answer: ASINs starting B0NOTFOUND are not found (left out,
			// as Amazon leaves them out), B0OUTSTOCK is out of stock, the three ASINs recorded live (C1Q1) get Amazon's
			// real answer; any other is found and in stock.
			$items = array();
			foreach ( (array) ( $body['itemIds'] ?? array() ) as $asked ) {
				$asin = strtoupper( (string) $asked );
				$file = array(
					'B0NOTFOUND' => 'getItems-empty.json',
					'B0OUTSTOCK' => 'getItems-oos.json',
					'B000IXRCTQ' => 'live-B000IXRCTQ-all.json',
					'B0DHLWFD84' => 'live-B0DHLWFD84-all.json',
					'B0F7RNL68R' => 'live-B0F7RNL68R-all.json',
				)[ substr( $asin, 0, 10 ) ] ?? 'getItems-ok.json';
				$one  = json_decode(
					$answer(
						200,
						$file,
						array(
							'{{ASIN}}'        => $asin,
							'{{MARKETPLACE}}' => (string) ( $body['marketplace'] ?? 'www.amazon.com' ),
						)
					)['body'],
					true
				);
				foreach ( (array) ( $one['itemsResult']['items'] ?? array() ) as $item ) {
					$items[] = $item;
				}
			}

			$response         = $answer( 200, 'getItems-empty.json' );
			$response['body'] = (string) wp_json_encode( array() === $items ? array() : array( 'itemsResult' => array( 'items' => $items ) ) );
			return $response;
		}

		if ( str_ends_with( $path, '/searchItems' ) ) {
			// Ten items a page from searchItems.json (C1M), thirty in all: on page P the ASINs are B0SRP0000N and the titles
			// "Stub Volleyball P-N" (N 0-9); keywords "nothing" find none.
			$item_page = max( 1, (int) ( $body['itemPage'] ?? 1 ) );
			if ( 'nothing' === ( $body['keywords'] ?? '' ) || $item_page > 3 ) {
				$none         = $answer( 200, 'getItems-empty.json' );
				$none['body'] = '{"searchResult":{"totalResultCount":0,"items":[]}}';
				return $none;
			}
			$items = array();
			for ( $n = 0; $n < 10; $n++ ) {
				$one     = json_decode(
					$answer(
						200,
						'searchItems.json',
						array(
							'{{ASIN}}'        => 'B0SR' . $item_page . '0000' . $n,
							'{{TITLE}}'       => "Stub Volleyball {$item_page}-{$n}",
							'{{MARKETPLACE}}' => (string) ( $body['marketplace'] ?? 'www.amazon.com' ),
						)
					)['body'],
					true
				);
				$items[] = $one['searchResult']['items'][0];
				$total   = $one['searchResult']['totalResultCount'];
			}
			$response         = $answer( 200, 'searchItems.json' );
			$response['body'] = (string) wp_json_encode(
				array(
					'searchResult' => array(
						'totalResultCount' => $total,
						'items'            => $items,
					),
				)
			);
			return $response;
		}

		return $answer( 404, 'error-401.json' );
	},
	10,
	3
);
