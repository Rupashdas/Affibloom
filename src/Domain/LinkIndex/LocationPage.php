<?php
/**
 * One page of a product's Locations tab.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\LinkIndex;

use Affibloom\Domain\PageNumbers;

/**
 * One page of a product's Locations tab (F12 L5; Task 36B): the host posts on it, and how many hosts there are in all;
 * pages count hosts, not rows. Its page arithmetic is GroupPage's (one page for an empty list; a page past the end is
 * the last page; PageNumbers).
 */
final class LocationPage {

	use PageNumbers;

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
}
