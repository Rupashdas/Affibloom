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

/**
 * The product a cloaked slug names (F24 R17, D3): a published or retired product with that slug (Lasso redirects its
 * `lasso_delete` links too; a trashed product's slug carries `__trashed`, so it is not found), else the product that had
 * it before (F24 N1; Task CL10): no post is rewritten when a slug changes, so the old address keeps answering. URL
 * variants (V3) add their arm here.
 */
final class CloakResolver {

	/**
	 * Builds the resolver.
	 *
	 * @param ProductRepository $products The library.
	 */
	public function __construct( private ProductRepository $products ) {}

	/**
	 * The product, or null.
	 *
	 * @param string $slug The slug from the path.
	 */
	public function product_for( string $slug ): ?Product {
		return $this->products->find_by_slug( $slug ) ?? $this->products->find_by_old_slug( $slug );
	}
}
