<?php
/**
 * A link's domain, as opportunities compare them.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Opportunity;

/**
 * Lasso's base domain (F22 D5; `get_base_domain`): a URL's host in lower case without a leading `www.`; '' for a URL
 * with no host. A product's is stored as `_affibloom_domain`; the link index's `domain` column holds the same for
 * external links.
 */
final class BaseDomain {

	/**
	 * The URL's base domain.
	 *
	 * @param string $url The URL.
	 */
	public static function of( string $url ): string {
		$host = parse_url( trim( $url ), PHP_URL_HOST ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Domain code does not call WordPress.
		if ( ! is_string( $host ) || '' === $host ) {
			return '';
		}
		$host = strtolower( $host );

		return str_starts_with( $host, 'www.' ) ? substr( $host, 4 ) : $host;
	}

	/**
	 * The domain a product is matched by (`_affibloom_domain`): its URL's, except that an Amazon short link (`amzn.to`,
	 * `amzn.com`) is Amazon's. Lasso resolves the short link when it stores the product, so its base domain is
	 * amazon.com, which the lists leave out (F22 R71, R96); kept as amzn.to, the product would take every short link on
	 * the site as its opportunities.
	 *
	 * @param string $url The product's URL.
	 */
	public static function of_product( string $url ): string {
		$domain = self::of( $url );

		return in_array( $domain, array( 'amzn.to', 'amzn.com' ), true ) ? 'amazon.com' : $domain;
	}
}
