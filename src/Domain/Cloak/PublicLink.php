<?php
/**
 * Where a product's links go.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Cloak;

use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductType;

/**
 * A product's links (F24 R13, R21, D6, D9). The destination is the link the product's box links to: its stored Primary
 * URL, which the save already made Lasso's (Amazon's tag rule; Keep Original URL and geni.us kept as typed). The public
 * link is the cloaked address (`<home>/<prefix>/<slug>/`) when Link Cloaking is on and the product is not Amazon's,
 * else the destination; with the stored URL already the destination, Lasso's "kept URL" branch of R13 gives the same.
 */
final class PublicLink {

	/**
	 * Where the product's cloaked address sends a reader; '' when it has no link.
	 *
	 * @param Product $product The product.
	 */
	public static function destination( Product $product ): string {
		return trim( $product->url->value() );
	}

	/**
	 * The address the product's links show (R13).
	 *
	 * @param Product $product The product.
	 * @param string  $home    The site's home address.
	 * @param string  $prefix  The Cloaked Link Prefix, '' for none.
	 */
	public static function for( Product $product, string $home, string $prefix ): string {
		if ( ! $product->cloak || ProductType::Amazon === $product->type || '' === $product->slug ) {
			return self::destination( $product );
		}

		return self::cloaked( $product->slug, $home, $prefix );
	}

	/**
	 * A slug's cloaked address: `<home>/<prefix>/<slug>/`, or `<home>/<slug>/` without a prefix.
	 *
	 * @param string $slug   The slug.
	 * @param string $home   The site's home address.
	 * @param string $prefix The Cloaked Link Prefix, '' for none.
	 */
	public static function cloaked( string $slug, string $home, string $prefix ): string {
		$prefix = trim( $prefix, '/' );

		return rtrim( $home, '/' ) . '/' . ( '' === $prefix ? '' : $prefix . '/' ) . $slug . '/';
	}
}
