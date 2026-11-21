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
}
