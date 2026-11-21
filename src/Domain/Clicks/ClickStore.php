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

	/**
	 * Removes the rows of days before this one (ADR-005's 400 days; Task 33C).
	 *
	 * @param string $day The first UTC day kept, "Y-m-d".
	 */
	public function delete_before( string $day ): void;

	/**
	 * Each product's clicks from a day on (Task 34A).
	 *
	 * @param string    $day         The first UTC day counted, "Y-m-d".
	 * @param list<int> $product_ids The products.
	 * @return array<int, int> By product ID; a product without clicks is left out.
	 */
	public function totals_since( string $day, array $product_ids ): array;

	/**
	 * All published products' clicks from a day on (the Dashboard's Clicks (30 days), Task 34B).
	 *
	 * @param string $day The first UTC day counted, "Y-m-d".
	 */
	public function total_since( string $day ): int;
}
