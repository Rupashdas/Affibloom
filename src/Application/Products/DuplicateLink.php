<?php
/**
 * The Primary URL being saved belongs to another product; nothing was saved.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Products;

use Affibloom\Domain\Product\Product;
use RuntimeException;

/**
 * The Primary URL being saved belongs to another published product (Lasso's repoint_offer, F1 requirements 9 S10).
 * The product page offers "This Product Already Exists" with that product.
 */
final class DuplicateLink extends RuntimeException {

	/**
	 * Builds the error.
	 *
	 * @param Product $other The product that already has the link.
	 */
	public function __construct( public readonly Product $other ) {
		parent::__construct( 'The link belongs to product ' . (int) $other->id );
	}
}
