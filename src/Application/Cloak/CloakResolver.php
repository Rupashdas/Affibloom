<?php
/**
 * Finds the product a cloaked address belongs to.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Cloak;

use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Domain\Variant\VariantRepository;

/**
 * The product a cloaked slug names (F24 R17, D3): a published or retired product with that slug (Lasso redirects its
 * `lasso_delete` links too; a trashed product's slug carries `__trashed`, so it is not found), else the product that had
 * it before (F24 N1; Task CL10): no post is rewritten when a slug changes, so the old address keeps answering. A URL
 * variant's permalink answers as its product (F27 R18; Task V3), at the root only and after every product's slug.
 */
final class CloakResolver {

	/**
	 * Builds the resolver.
	 *
	 * @param ProductRepository      $products The library.
	 * @param VariantRepository|null $variants The products' URL variants (Task V3); null knows none.
	 */
	public function __construct( private ProductRepository $products, private ?VariantRepository $variants = null ) {}

	/**
	 * The product, or null.
	 *
	 * @param string $slug The slug from the path.
	 */
	public function product_for( string $slug ): ?Product {
		return $this->products->find_by_slug( $slug ) ?? $this->products->find_by_old_slug( $slug );
	}

	/**
	 * The product whose link an address sends readers to (F24 D3, the user's decision of 2026-11-24; deviation 165): a
	 * retired product that points at a published one sends them there, as Lasso's repoint does for its boxes; any other
	 * product, or one whose target is gone, sends them to its own link.
	 *
	 * @param Product $product The product at the address.
	 */
	public function sends_to( Product $product ): Product {
		$target = (int) $this->products->repointed_to( (int) $product->id );

		return $target > 0 && $target !== $product->id ? ( $this->products->get( $target ) ?? $product ) : $product;
	}

	/**
	 * The product a permalink variant's key belongs to, or null (F27 R18).
	 *
	 * @param string $key The path's last part.
	 */
	public function product_for_variant( string $key ): ?Product {
		$slug = null === $this->variants ? null : $this->variants->parent_slug_for_permalink( $key );

		return null === $slug ? null : $this->products->find_by_slug( $slug );
	}
}
