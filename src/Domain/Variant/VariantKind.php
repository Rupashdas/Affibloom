<?php
/**
 * What a product's variants are.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Variant;

use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductType;

/**
 * F27 R2: an Amazon product's variants are tracking IDs, any other product's cloaked permalinks. The kind is the
 * product's now (R23: rows stored under the other kind show and are saved as this one).
 */
enum VariantKind: string {

	case Permalink = 'permalink';
	case AmazonTag = 'amazon_tag';

	/**
	 * The kind of a product's variants.
	 *
	 * @param Product $product The product.
	 */
	public static function of( Product $product ): self {
		return ProductType::Amazon === $product->type ? self::AmazonTag : self::Permalink;
	}
}
