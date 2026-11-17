<?php
/**
 * Where a product's links go.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Cloak;

use Affibloom\Domain\Product\Product;

/**
 * A product's links (F24 R13, R21, D6, D9). The destination is the link the product's box links to: its stored Primary
 * URL, which the save already made Lasso's (Amazon's tag rule, Keep Original URL kept as typed). The public link
 * (the cloaked address or the destination) is Task CL3's.
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
}
