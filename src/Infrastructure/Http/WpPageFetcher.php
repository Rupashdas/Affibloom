<?php
/**
 * Fetches a page with WordPress's HTTP API, following redirects by hand.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\Http;

use Affibloom\Application\Products\FetchedPage;
use Affibloom\Application\Products\PageFetcher;
use Affibloom\Domain\Amazon\AmazonUrl;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\AffiliateUrl;
use WP_Http;

/**
 * Fetches a page with WordPress's HTTP API, as Lasso does (wp_remote_get with the editor's browser user agent and
 * WordPress's 5-second timeout). Redirects are followed by hand, up to WordPress's usual five, so that a redirect
 * into an Amazon store stops before the store's page is requested; Lasso lets wp_remote_get follow it. An Amazon
 * short link on the way (amzn.to → amzn.to) is followed, since it is only a redirect (Task 12D).
 */
final class WpPageFetcher implements PageFetcher {

	/** WordPress's default redirection limit. */
	private const MAX_REDIRECTS = 5;

	/** A title sits at the top of a page; more than this is never read. */
	private const MAX_BYTES = 1048576;

	/** The user agent when there is no browser to borrow it from (a bulk add runs in WP-Cron). */
	private const FALLBACK_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36';

	/**
	 * The page the link ends at, or null when there was no answer.
	 *
	 * @param string $url An http(s) link.
	 */
	public function fetch( string $url ): ?FetchedPage {
		$current = $url;

		for ( $hop = 0; $hop <= self::MAX_REDIRECTS; $hop++ ) {
			$response = wp_remote_get(
				$current,
				array(
					'redirection'         => 0,
					'timeout'             => 5,
					'user-agent'          => $this->user_agent(),
					'limit_response_size' => self::MAX_BYTES,
				)
			);
			if ( is_wp_error( $response ) ) {
				return null;
			}

			$status   = (int) wp_remote_retrieve_response_code( $response );
			$location = $this->location( $response );
			if ( $status < 300 || $status >= 400 || '' === $location ) {
				return new FetchedPage( $status, $current, (string) wp_remote_retrieve_body( $response ) );
			}

			$next = WP_Http::make_absolute_url( $location, $current );
			if ( $this->is_amazon_store( $next ) ) {
				return new FetchedPage( $status, $next, '' );
			}
			$current = $next;
		}

		return null;
	}

	/**
	 * The redirect target, or ''. A response can carry the header more than once; the last one counts.
	 *
	 * @param array<string, mixed> $response A wp_remote_get() response.
	 */
	private function location( array $response ): string {
		$location = wp_remote_retrieve_header( $response, 'location' );
		if ( is_array( $location ) ) {
			$location = (string) end( $location );
		}
		return trim( (string) $location );
	}

	/**
	 * The editor's browser user agent, as Lasso sends it, or a browser-like one in the background.
	 */
	private function user_agent(): string {
		$agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		return '' !== $agent ? $agent : self::FALLBACK_USER_AGENT;
	}

	/**
	 * Whether a URL is an Amazon store's (an Amazon link that is not a short link).
	 *
	 * @param string $url Any URL.
	 */
	private function is_amazon_store( string $url ): bool {
		try {
			$link = AffiliateUrl::from_string( $url );
		} catch ( InvalidField $not_a_link ) {
			return false;
		}
		return $link->is_amazon() && ! AmazonUrl::is_short( $link );
	}
}
