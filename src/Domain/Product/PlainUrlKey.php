<?php
/**
 * A link's key without its query string.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Product;

use Affibloom\Domain\Amazon\AmazonUrl;

/**
 * The key two links to one page share when "Allow duplicate destinations" is off (F9 R36, Task 9C): the link as
 * AffiliateUrl::key() has it, without its query string or fragment. Lasso keeps the query for the pages where it names
 * the product (its DOMAIN_ALLOW_KEEP_FINAL_URL_PARAMS: brooksbrothers.com and mechanicalkeyboards.com). An Amazon
 * link has no plain key: Amazon products are found by their ASIN or search keywords, never by a path.
 */
final class PlainUrlKey {

	/** Hosts whose query string says which product the page shows. */
	private const KEEP_QUERY = array( 'brooksbrothers.com', 'mechanicalkeyboards.com' );

	/**
	 * The plain key of a link, or an empty string when the link has none (an Amazon link).
	 *
	 * @param AffiliateUrl $url The link.
	 */
	public static function of( AffiliateUrl $url ): string {
		if ( null !== AmazonUrl::from( $url ) || $url->is_amazon() ) {
			return '';
		}

		$key  = $url->key();
		$host = strtolower( (string) parse_url( $key, PHP_URL_HOST ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Domain code does not call WordPress.
		foreach ( self::KEEP_QUERY as $kept ) {
			if ( $host === $kept || str_ends_with( $host, '.' . $kept ) ) {
				return $key;
			}
		}

		$plain = (string) strtok( (string) strtok( $key, '#' ), '?' );

		return rtrim( $plain, '/' );
	}
}
