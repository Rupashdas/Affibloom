<?php
/**
 * A shortcode's attributes as Lasso reads them.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Shortcode;

/**
 * Lasso's repairs of parsed attributes (F31 R4, R5; Lasso's shortcode 224-234, Helper 2104-2168): an integer key whose
 * value holds `"` (a broken `key="value"` pair) becomes key = the part before the first `=`, value = the part after the
 * last, quotes removed; every value loses a leading and trailing `&quot;`; names are lower case, as WordPress gives them.
 */
final class ShortcodeAttributes {

	/**
	 * The repaired attributes.
	 *
	 * @param array<int|string, mixed>|string $raw The shortcode's attributes as WordPress parsed them ('' for none).
	 * @return array<string, string>
	 */
	public static function repaired( array|string $raw ): array {
		$out = array();
		foreach ( is_array( $raw ) ? $raw : array() as $key => $value ) {
			if ( ! is_scalar( $value ) ) {
				continue;
			}
			$value = (string) $value;
			if ( is_int( $key ) ) {
				if ( ! str_contains( $value, '"' ) || ! str_contains( $value, '=' ) ) {
					continue;
				}
				$parts = explode( '=', $value );
				$key   = strtolower( trim( $parts[0] ) );
				$value = str_replace( '"', '', (string) end( $parts ) );
			}
			$out[ strtolower( (string) $key ) ] = self::unquoted( $value );
		}

		return $out;
	}

	/**
	 * A value without a leading and trailing `&quot;` (R5).
	 *
	 * @param string $value The value.
	 */
	private static function unquoted( string $value ): string {
		if ( str_starts_with( $value, '&quot;' ) ) {
			$value = substr( $value, 6 );
		}
		if ( str_ends_with( $value, '&quot;' ) ) {
			$value = substr( $value, 0, -6 );
		}

		return $value;
	}
}
