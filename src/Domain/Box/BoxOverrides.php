<?php
/**
 * What one box asks of its product: which product, and later the box's own texts and values.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Box;

/**
 * One box's overrides (F5 R65-R89, F7). Task 18A reads the product only; Tasks 20A-20C add the overrides, and the
 * shortcodes (18S1, 27A) read the same fields from their attributes.
 */
final class BoxOverrides {

	/**
	 * Use from_block().
	 *
	 * @param int $product_id The product's ID; 0 when none was given.
	 */
	private function __construct( public readonly int $product_id ) {}

	/**
	 * Reads the block's attributes (`affibloom/product-box`). A missing or malformed ID is 0, which draws nothing.
	 *
	 * @param array<string, mixed> $attributes The block's attributes.
	 */
	public static function from_block( array $attributes ): self {
		$id = $attributes['productId'] ?? 0;

		return new self( is_numeric( $id ) ? max( 0, (int) $id ) : 0 );
	}
}
