<?php
/**
 * The destination an affiliate link carries inside itself, e.g. Impact's "u=".
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Product;

/**
 * The destination an affiliate link carries inside itself: Lasso_Helper::get_final_url_from_url_param(). Lasso names a
 * link after this destination's site without requesting anything, which is how the site's Impact links become
 * "Mizunousa".
 */
final class EmbeddedDestination {

	/**
	 * The destination, or null when the link carries none. The network rules come first; otherwise the first query
	 * parameter that is itself a link, except "referrer". What is found is repaired as a pasted link would be, and
	 * kept only if it is then a link; its trailing slash is dropped.
	 *
	 * @param string $link An http(s) link.
	 */
	public static function in( string $link ): ?string {
		if ( ! self::is_link( $link ) ) {
			return null;
		}

		$host = strtolower( (string) parse_url( $link, PHP_URL_HOST ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Domain code does not call WordPress.
		$host = str_starts_with( $host, 'www.' ) ? substr( $host, 4 ) : $host;

		$found = match ( true ) {
			in_array( $host, array( 'shareasale.com', 'shareasale-analytics.com' ), true ) => self::argument( $link, 'urllink' ),
			in_array( $host, array( 'pntra.com', 'wordseed.com' ), true ) => self::argument( $link, 'url' ),
			'titan.fitness' === $host => self::titan_destination( $link ),
			'prf.hn' === $host => self::prf_destination( $link ),
			default => self::first_link_parameter( $link ),
		};

		$found = AffiliateUrl::repair( trim( $found ) );
		return '' !== $found && self::is_link( $found ) ? trim( $found, '/' ) : null;
	}

	/**
	 * Lasso_Helper::validate_url(): starts with "http://" or "https://" and passes PHP's URL filter, with spaces read
	 * as %20 and characters outside Latin-1 ignored.
	 *
	 * @param string $url Candidate.
	 */
	private static function is_link( string $url ): bool {
		$url = (string) preg_replace( '/[^\x00-\xFF]+/u', '', str_replace( ' ', '%20', $url ) );

		return ( str_starts_with( $url, 'http://' ) || str_starts_with( $url, 'https://' ) )
			&& false !== filter_var( $url, FILTER_VALIDATE_URL );
	}

	/**
	 * One query argument (Lasso_Helper::get_argument_from_url(), which reads "&amp;" as "&").
	 *
	 * @param string $link Link.
	 * @param string $name Argument name.
	 */
	private static function argument( string $link, string $name ): string {
		$value = self::query( str_replace( '&amp;', '&', $link ) )[ $name ] ?? '';
		return is_string( $value ) ? $value : '';
	}

	/**
	 * The first query parameter, other than "referrer", that is a link.
	 *
	 * @param string $link Link.
	 */
	private static function first_link_parameter( string $link ): string {
		foreach ( self::query( $link ) as $key => $value ) {
			if ( 'referrer' === $key || ! is_string( $value ) ) {
				continue;
			}
			$value = str_replace( ' ', '%20', $value );
			if ( self::is_link( $value ) ) {
				return $value;
			}
		}
		return '';
	}

	/**
	 * Titan Fitness carries a path on its own site in "redirect".
	 *
	 * @param string $link Link.
	 */
	private static function titan_destination( string $link ): string {
		$path = self::argument( $link, 'redirect' );
		return '' === $path ? '' : 'https://www.titan.fitness' . $path;
	}

	/**
	 * Partnerize (prf.hn) puts the destination in a "destination:" path segment.
	 *
	 * @param string $link Link.
	 */
	private static function prf_destination( string $link ): string {
		$found = '';
		foreach ( explode( '/', (string) parse_url( $link, PHP_URL_PATH ) ) as $segment ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Domain code does not call WordPress.
			if ( str_contains( $segment, 'destination:' ) ) {
				$candidate = urldecode( str_replace( 'destination:', '', $segment ) );
				$found     = self::is_link( $candidate ) ? $candidate : $found;
			}
		}
		return $found;
	}

	/**
	 * The query string's arguments, decoded.
	 *
	 * @param string $link Link.
	 * @return array<int|string, mixed>
	 */
	private static function query( string $link ): array {
		$arguments = array();
		parse_str( (string) parse_url( $link, PHP_URL_QUERY ), $arguments ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Domain code does not call WordPress.
		return $arguments;
	}
}
