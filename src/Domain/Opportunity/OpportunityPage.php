<?php
/**
 * One page of a list of opportunities.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Opportunity;

use Affibloom\Domain\PageNumbers;

/**
 * One page of an Opportunities list (F22 R4, R7; Tasks O1-O3): ten rows, the total behind them ("N Found"), and a page
 * past the end shown as the last one.
 */
final class OpportunityPage {

	use PageNumbers;

	/** Lasso's page size. */
	public const PER_PAGE = 10;

	/**
	 * Builds the page.
	 *
	 * @param list<Opportunity|DomainCount|DetectedLink|ContentRow> $items The rows on this page.
	 * @param int                                                   $total    Rows in the whole list.
	 * @param int                                                   $page     This page's number, from 1.
	 * @param int                                                   $per_page Rows per page.
	 */
	public function __construct(
		public readonly array $items,
		public readonly int $total,
		public readonly int $page,
		public readonly int $per_page = self::PER_PAGE,
	) {}
}
