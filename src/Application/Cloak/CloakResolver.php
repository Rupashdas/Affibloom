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
 * `lasso_delete` links too; a trashed product's slug carries `__trashed`, so it is not found). Old slugs (CL10) and URL
 * variants (V3) add their arms here.
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
		return $this->products->find_by_slug( $slug );
	}
}
