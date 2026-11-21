<?php
/**
 * Reading a network link's IDs and deep link.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Monetize;

/**
 * Lasso's auto-monetize classes as one reader (F35 R7, R9; Task C2A; Appendix B): the link is HTML-entity-decoded; an
 * ID is the first of the network's query keys with a value, keys read case-insensitively and the value cut at a
 * literal `u0026`; the deep link is its query key read as written, HTML-decoded, and sets the base domain. Impact reads
 * its IDs from the path; Partnerize its deep link from after `/destination:`.
 */
final class NetworkExtractor {

	/** Impact's path rule (R9): /<4-12 word characters>, or /c/<affiliate>/<campaign>/<advertiser>. */
	private const IMPACT = '~[\d\w]+\.\w+(\/(\w{4,12}))|(\/c\/(\d+)\/(\d+)\/(\d+))~';

	/**
	 * What a link of this network says.
	 *
	 * @param Network $network The network (NetworkRegistry::match()).
	 * @param string  $url     The link.
	 */
	public static function read( Network $network, string $url ): DetectedLink {
		$url                    = html_entity_decode( $url );
		[ $deep_link, $domain ] = self::deep_link( $network->deep_link, $url );

		return new DetectedLink( $network, self::id( $network->affiliate, $url, 4, 2 ), self::id( $network->advertiser, $url, 6, 0 ), $deep_link, $domain );
	}

	/**
	 * An ID: Impact's path groups, else the first query key with a value.
	 *
	 * @param list<string> $keys    The keys, or Network::IMPACT_PATH.
	 * @param string       $url     The decoded link.
	 * @param int          $group   Impact's group for it.
	 * @param int          $or_else Impact's group when that one is missing (0: none).
	 */
	private static function id( array $keys, string $url, int $group, int $or_else ): string {
		if ( array( Network::IMPACT_PATH ) === $keys ) {
			preg_match( self::IMPACT, $url, $found );
			$value = (string) ( $found[ $group ] ?? '' );

			return '' === $value && $or_else > 0 ? (string) ( $found[ $or_else ] ?? '' ) : $value;
		}
		$query = array_change_key_case( self::query( $url ), CASE_LOWER );
		foreach ( $keys as $key ) {
			$value = $query[ $key ] ?? '';
			$value = is_string( $value ) ? explode( 'u0026', $value )[0] : '';
			if ( '' !== $value ) {
				return $value;
			}
		}

		return '';
	}

	/**
	 * The deep link and its base domain.
	 *
	 * @param string $rule A query key, Network::DESTINATION, or ''.
	 * @param string $url  The decoded link.
	 * @return array{0: string, 1: string}
	 */
	private static function deep_link( string $rule, string $url ): array {
		if ( '' === $rule ) {
			return array( '', '' );
		}
		if ( Network::DESTINATION === $rule ) {
			$link = explode( '/destination:', $url )[1] ?? '';
			$link = '' === $link ? '' : urldecode( html_entity_decode( $link ) );
		} else {
			$value = self::query( $url )[ $rule ] ?? '';
			$link  = is_string( $value ) ? html_entity_decode( $value ) : '';
		}

		return array( $link, '' === $link ? '' : NetworkRegistry::base_domain( $link ) );
	}

	/**
	 * The link's query as PHP reads it (Lasso's `get_argument_from_url()`, "&amp;" read as "&").
	 *
	 * @param string $url The link.
	 * @return array<mixed>
	 */
	private static function query( string $url ): array {
		$query = parse_url( str_replace( '&amp;', '&', $url ), PHP_URL_QUERY ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Domain code does not call WordPress.
		$found = array();
		parse_str( is_string( $query ) ? $query : '', $found );

		return $found;
	}
}
