<?php
/**
 * One page of a product's Locations tab.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\LinkIndex;

/**
 * One page of a product's Locations tab (F12 L5; Task 36B): the host posts on it, and how many hosts there are in all;
 * pages count hosts, not rows. Its page arithmetic is GroupPage's (one page for an empty list; a page past the end is
 * the last page).
 */
final class LocationPage {

	/** Lasso's page size (view-js.php 32-41). */
	public const PER_PAGE = 10;

	/**
	 * Builds the page.
	 *
	 * @param list<LocationHost> $items    The hosts on this page.
	 * @param int                $total    Hosts in the whole list.
	 * @param int                $page     This page's number, from 1.
	 * @param int                $per_page Hosts per page.
	 */
	public function __construct(
		public readonly array $items,
		public readonly int $total,
		public readonly int $page,
		public readonly int $per_page = self::PER_PAGE,
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
	 * The first host's number in the whole list ("1" in "1-10 of 11"); 0 when empty.
	 */
	public function first(): int {
		return 0 === $this->total ? 0 : ( $this->current() - 1 ) * $this->per_page + 1;
	}

	/**
	 * The last host's number in the whole list ("10" in "1-10 of 11"); 0 when empty.
	 */
	public function last(): int {
		return 0 === $this->total ? 0 : min( $this->current() * $this->per_page, $this->total );
	}

	/**
	 * Every place's index row on this page (the Clicks column, Task 34C).
	 *
	 * @return list<int>
	 */
	public function place_ids(): array {
		$ids = array();
		foreach ( $this->items as $host ) {
			foreach ( $host->places as $place ) {
				$ids[] = $place->id;
			}
		}

		return $ids;
	}

	/**
	 * Where the page starts in the whole list, for the query.
	 */
	public function offset(): int {
		return ( $this->current() - 1 ) * $this->per_page;
	}
}
