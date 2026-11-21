<?php
/**
 * Where clicks are counted.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Clicks;

/**
 * The click counts (F12 D2, ADR-005; Task 33A): one daily total per product, host post, location and button.
 */
interface ClickStore {

	/**
	 * Adds one click to its day's total.
	 *
	 * @param string $day        The UTC day, "Y-m-d".
	 * @param int    $product_id The product.
	 * @param int    $post_id    The post it was clicked in; 0 when none.
	 * @param int    $location   The link index row (`data-affibloom-location`); 0 when none.
	 * @param Button $button     What was clicked.
	 */
	public function add( string $day, int $product_id, int $post_id, int $location, Button $button ): void;
}
