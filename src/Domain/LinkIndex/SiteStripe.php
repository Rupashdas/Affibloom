<?php
/**
 * Amazon's SiteStripe image and iframe addresses.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\LinkIndex;

use Affibloom\Domain\Amazon\Marketplace;

/**
 * SiteStripe markup (F21 R31; Lasso_Amazon_Api::get_site_stripe_url): an image or iframe served from Amazon's widget
 * hosts names a product by its `asins` (iframe) or `asin` (image), its tag by `tracking_id` or `tag`, and its store by
 * `region`, else `marketplace`, else the United States.
 */
final class SiteStripe {

	/** The widget addresses SiteStripe markup is served from. */
	private const HOSTS = array( 'ws-na.amazon-adsystem.com/widgets/q', 'ws-eu.amazon-adsystem.com/widgets/q' );

	/**
	 * Whether an address is SiteStripe's.
	 *
	 * @param string $src An image's or iframe's `src`.
	 */
	public static function is( string $src ): bool {
		foreach ( self::HOSTS as $host ) {
			if ( str_contains( $src, $host ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * The Amazon product link the markup names ("https://www.amazon.com/dp/<ASIN>/?tag=<tag>"), or null when the
	 * address is not SiteStripe's or names no ASIN.
	 *
	 * @param string $src An image's or iframe's `src`.
	 */
	public static function amazon_url( string $src ): ?string {
		if ( ! self::is( $src ) ) {
			return null;
		}
		parse_str( (string) parse_url( str_replace( '&amp;', '&', $src ), PHP_URL_QUERY ), $query ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Domain code does not call WordPress.
		$value = static fn( string ...$names ): string => (string) ( array_values( array_filter( array_map( static fn( string $name ): string => is_string( $query[ $name ] ?? null ) ? trim( $query[ $name ] ) : '', $names ) ) )[0] ?? '' );

		$asin = $value( 'asins', 'asin', 'ASIN' );
		if ( '' === $asin ) {
			return null;
		}
		$region = strtolower( $value( 'region', 'marketplace' ) );
		$region = 'uk' === $region ? 'gb' : $region;

		return 'https://' . Marketplace::host( $region ) . '/dp/' . rawurlencode( $asin ) . '/?tag=' . rawurlencode( $value( 'tracking_id', 'tag' ) );
	}
}
