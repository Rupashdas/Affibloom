<?php
/**
 * One page of the Dashboard list, and the total behind it.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Product;

/**
 * One page of the Dashboard list, and the total behind it.
 */
final class ProductPage {

	/**
	 * Builds the page.
	 *
	 * @param list<Product> $items    Products on this page, in the list's order.
	 * @param int           $total    Products behind the list, across every page.
	 * @param int           $page     This page's number, from 1.
	 * @param int           $per_page Products per page.
	 */
	public function __construct(
		public readonly array $items,
		public readonly int $total,
		public readonly int $page,
		public readonly int $per_page,
	) {}

	/**
	 * Number of pages; 1 when there is nothing to show, because Lasso's pager draws one page either way.
	 */
	public function pages(): int {
		return max( 1, (int) ceil( $this->total / max( 1, $this->per_page ) ) );
	}

	/**
	 * Whether this page has nothing on it.
	 */
	public function is_empty(): bool {
		return array() === $this->items;
	}

	/**
	 * The first product's number in the whole list: the "1" in "1-10 of 263"; 0 when the list is empty.
	 * A page past the end gives the last page's range, as Lasso's range does (view-js.php,
	 * lasso_update_link_range: start_index > total falls back to the last page).
	 */
	public function first(): int {
		if ( 0 === $this->total ) {
			return 0;
		}

		return ( $this->current() - 1 ) * $this->per_page + 1;
	}

	/**
	 * The last product's number in the whole list: the "10" in "1-10 of 263"; 0 when the list is empty.
	 */
	public function last(): int {
		if ( 0 === $this->total ) {
			return 0;
		}

		return min( $this->current() * $this->per_page, $this->total );
	}

	/**
	 * This page's number, never past the last page and never below 1.
	 */
	public function current(): int {
		return max( 1, min( $this->page, $this->pages() ) );
	}
}
