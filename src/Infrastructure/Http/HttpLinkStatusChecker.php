<?php
/**
 * A link's HTTP status, asked with WordPress's HTTP API.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\Http;

use Affibloom\Application\LinkHealth\LinkStatusChecker;
use Affibloom\Domain\LinkHealth\CheckResult;

/**
 * Lasso's send_request( 'get', $url ) (F11 R12, D6, D9): a GET through wp_safe_remote_get(), 25 seconds, WordPress's
 * five redirects, WordPress's own user agent (Lasso passes no headers), at most 32 KB read. 200 is OK; any other
 * answer is a failure with its status, and no answer (an error, too many redirects) is a failure with 500. A request
 * WordPress refused to send (WP_HTTP_BLOCK_EXTERNAL: blocked hosts, the Local copy) is no check at all.
 */
final class HttpLinkStatusChecker implements LinkStatusChecker {

	/**
	 * What the link answered.
	 *
	 * @param string $url The link.
	 */
	public function status( string $url ): CheckResult {
		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'             => 25,
				'redirection'         => 5,
				'limit_response_size' => 32 * KB_IN_BYTES,
			)
		);
		if ( is_wp_error( $response ) ) {
			return 'http_request_not_executed' === $response->get_error_code() ? CheckResult::not_executed() : CheckResult::failed( 0 );
		}
		$status = (int) wp_remote_retrieve_response_code( $response );

		return 200 === $status ? CheckResult::ok() : CheckResult::failed( $status );
	}
}
