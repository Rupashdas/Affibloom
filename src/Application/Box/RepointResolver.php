<?php
/**
 * Which product a box draws, after any repoint.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Box;

use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductRepository;

/**
 * The product a box draws (Phase 3 decisions, "Kept from old Task 18A"; F1 D14, X10, D41, D43). A product retired by a
 * repoint is drawn as the product it points to, as Lasso's front end follows lasso_repointed_to_lasso_id: whatever the
 * status, up to 50 hops, stopping at a target that no longer exists (a product deleted for good) or that was already
 * seen. The product reached is drawn only when it is published; otherwise the box prints nothing (F5 R1).
 * Shared by the block and Affibloom's shortcode (18S1).
 */
final class RepointResolver {

	/** Lasso's limit on hops. */
	private const MAX_HOPS = 50;

	/**
	 * Builds the resolver.
	 *
	 * @param ProductRepository $products The products.
	 */
	public function __construct( private readonly ProductRepository $products ) {}

	/**
	 * The published product a box with this ID draws, or null.
	 *
	 * @param int $id The ID the box names.
	 */
	public function product( int $id ): ?Product {
		$current = $id;
		$seen    = array( $current => true );
		for ( $hop = 0; $hop < self::MAX_HOPS; $hop++ ) {
			$target = $this->products->repointed_to( $current );
			if ( null === $target ) {
				return null;
			}
			if ( 0 === $target || isset( $seen[ $target ] ) || null === $this->products->repointed_to( $target ) ) {
				break;
			}
			$seen[ $target ] = true;
			$current         = $target;
		}

		return $this->products->get( $current );
	}
}
