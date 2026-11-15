<?php
/**
 * One page of an Amazon product search.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Amazon;

/**
 * One page of an Amazon product search (C1M; Creators searchItems, R68): up to ten items and how many Amazon found.
 * Amazon gives at most ten pages of a search.
 */
final class SearchPage {

	/** Items on a page (Lasso's itemCount). */
	public const SIZE = 10;

	/** The last page Amazon gives (searchItems' itemPage is 1 to 10). */
	public const LAST = 10;

	/**
	 * Builds the page.
	 *
	 * @param list<CatalogItem> $items The items, in Amazon's order.
	 * @param int               $total How many items Amazon found in all.
	 * @param int               $page  This page's number, from 1.
	 */
	public function __construct( public readonly array $items, public readonly int $total, public readonly int $page ) {}

	/**
	 * Whether a next page can be asked for.
	 */
	public function has_more(): bool {
		return $this->page < self::LAST && $this->page * self::SIZE < $this->total;
	}
}
