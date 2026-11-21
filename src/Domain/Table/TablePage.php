<?php
/**
 * One page of the Tables list.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Table;

use Affibloom\Domain\PageNumbers;

/**
 * One page of the Tables list (F25 R8; Task T1): ten tables, oldest first, the total behind them ("x-y of n"), a page
 * past the end shown as the last one.
 */
final class TablePage {

	use PageNumbers;

	/** Lasso's page size. */
	public const PER_PAGE = 10;

	/**
	 * Builds the page.
	 *
	 * @param list<Table> $items    The tables on this page.
	 * @param int         $total    Tables in the whole list.
	 * @param int         $page     This page's number, from 1.
	 * @param int         $per_page Tables per page.
	 */
	public function __construct(
		public readonly array $items,
		public readonly int $total,
		public readonly int $page,
		public readonly int $per_page = self::PER_PAGE,
	) {}
}
