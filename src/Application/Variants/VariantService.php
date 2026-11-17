<?php
/**
 * A product's URL variants.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Variants;

use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Variant\Variant;
use Affibloom\Domain\Variant\VariantRepository;

/**
 * The Variants tab's use cases (F27): a product's variants, for the tab and its count.
 */
final class VariantService {

	/**
	 * Builds the service.
	 *
	 * @param VariantRepository $variants The stored variants.
	 */
	public function __construct( private VariantRepository $variants ) {}

	/**
	 * A product's variants, in the order they were stored.
	 *
	 * @param Product $product The product.
	 * @return list<Variant>
	 */
	public function list( Product $product ): array {
		return $this->variants->for_parent( (int) $product->id );
	}
}
