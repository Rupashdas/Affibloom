<?php
/**
 * Where comparison tables are kept.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Table;

use Affibloom\Domain\Product\SearchTerm;

/**
 * The comparison tables (F25 D1; Task T1).
 */
interface TableRepository {

	/**
	 * One page of tables, oldest first (R8): a search keeps those whose name, or a product's name, holds the words in
	 * order, anything between them.
	 *
	 * @param SearchTerm $term     The search.
	 * @param int        $page     The page, from 1.
	 * @param int        $per_page Tables per page.
	 */
	public function page( SearchTerm $term, int $page, int $per_page ): TablePage;

	/**
	 * How many tables there are ("Search All <n> Tables", R5).
	 */
	public function count(): int;

	/**
	 * One table, or null.
	 *
	 * @param int $id The table.
	 */
	public function get( int $id ): ?Table;
}
