<?php
/**
 * One page of the Groups list, and the total behind it.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Group;

/**
 * One page of the Groups list, and the total behind it. Its page arithmetic is ProductPage's (Lasso's pager draws one
 * page for an empty list; a page past the end is the last page).
 */
final class GroupPage {

	/**
	 * Builds the page.
	 *
	 * @param list<Group> $items    Groups on this page, in the list's order (newest first).
	 * @param int         $total    Groups behind the list, across every page.
	 * @param int         $page     This page's number, from 1.
	 * @param int         $per_page Groups per page.
	 */
	public function __construct(
		public readonly array $items,
		public readonly int $total,
		public readonly int $page,
		public readonly int $per_page,
	) {}

	/**
	 * Number of pages; 1 when there is nothing to show.
	 */
	public function pages(): int {
		return max( 1, (int) ceil( $this->total / max( 1, $this->per_page ) ) );
	}

	/**
	 * This page's number, never past the last page and never below 1.
	 */
	public function current(): int {
		return max( 1, min( $this->page, $this->pages() ) );
	}

	/**
	 * The first group's number in the whole list: the "1" in "1-10 of 11"; 0 when the list is empty.
	 */
	public function first(): int {
		return 0 === $this->total ? 0 : ( $this->current() - 1 ) * $this->per_page + 1;
	}

	/**
	 * The last group's number in the whole list: the "10" in "1-10 of 11"; 0 when the list is empty.
	 */
	public function last(): int {
		return 0 === $this->total ? 0 : min( $this->current() * $this->per_page, $this->total );
	}
}
