<?php
/**
 * Where comparison tables are used.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Table;

/**
 * Where tables are shown on the site (F25 R11, D13; Task T1), from F21's link index: the places whose display is
 * `table:<id>`, in posts of any status.
 */
interface TableLocations {

	/**
	 * How many places show each table.
	 *
	 * @param list<int> $table_ids The tables.
	 * @return array<int, int> By table; a table shown nowhere is left out.
	 */
	public function counts( array $table_ids ): array;

	/**
	 * One page of the posts that show a table (R40; Task T10): each post once, newest first.
	 *
	 * @param int $table    The table.
	 * @param int $page     The page, from 1.
	 * @param int $per_page Posts a page.
	 * @return array{items: list<array{id: int, title: string}>, total: int}
	 */
	public function page( int $table, int $page, int $per_page ): array;
}
