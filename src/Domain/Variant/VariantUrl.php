<?php
/**
 * The address a variant gives out.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Variant;

/**
 * F27 R9 (Lasso's url-variants.js 52-70): a permalink variant is `<home>/<key>/`, at the root whatever the Cloaked Link
 * Prefix (D4); an Amazon variant is the product's destination with its `tag` replaced by the key, or added; an empty key
 * gives the destination as it is.
 */
final class VariantUrl {

	/**
	 * The variant's address.
	 *
	 * @param VariantKind $kind        What the key is.
	 * @param string      $key         The key.
	 * @param string      $home        The site's home address.
	 * @param string      $destination The product's destination.
	 */
	public static function of( VariantKind $kind, string $key, string $home, string $destination ): string {
		if ( '' === $key ) {
			return $destination;
		}
		if ( VariantKind::Permalink === $kind ) {
			return rtrim( $home, '/' ) . '/' . $key . '/';
		}

		$tag      = 'tag=' . rawurlencode( $key );
		$fragment = '';
		$hash     = strpos( $destination, '#' );
		if ( false !== $hash ) {
			$fragment    = substr( $destination, $hash );
			$destination = substr( $destination, 0, $hash );
		}
		$tagged = preg_replace( '/([?&])tag=[^&]*/', '${1}' . $tag, $destination, 1, $count );
		if ( 0 === $count || null === $tagged ) {
			$tagged = $destination . ( str_contains( $destination, '?' ) ? '&' : '?' ) . $tag;
		}

		return $tagged . $fragment;
	}
}
