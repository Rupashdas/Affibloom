<?php
/**
 * A delete refused because posts still use the product.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Products;

use RuntimeException;

/**
 * Posts still use these products (the Locations count, F12 L1), so nothing was deleted (F12 L14-L16, D26; F1 D34, D35;
 * Task 36E). The routes answer 409 `affibloom_in_use`.
 */
final class ProductInUse extends RuntimeException {

	/**
	 * Builds the refusal.
	 *
	 * @param list<int> $ids The products in use.
	 */
	public function __construct( public readonly array $ids ) {
		parent::__construct( 'Products in use: ' . implode( ', ', $ids ) );
	}
}
