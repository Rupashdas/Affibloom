<?php
/**
 * The comparison tables' use cases.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Tables;

use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\SearchTerm;
use Affibloom\Domain\Table\Table;
use Affibloom\Domain\Table\TableLocations;
use Affibloom\Domain\Table\TablePage;
use Affibloom\Domain\Table\TableRepository;
use Affibloom\Domain\Table\TableStyle;

/**
 * F25's tables (Tasks T1-T17): the list first (T1). Later tasks add creating, editing and cloning.
 */
final class TableService {

	/**
	 * Builds the service.
	 *
	 * @param TableRepository $tables    The tables.
	 * @param TableLocations  $locations Where they are used (F21's index).
	 */
	public function __construct( private TableRepository $tables, private TableLocations $locations ) {}

	/**
	 * One page of the Tables list (R8).
	 *
	 * @param SearchTerm $term     The search.
	 * @param int        $page     The page, from 1.
	 * @param int        $per_page Tables per page (10; the editor's picker asks for 5).
	 */
	public function list( SearchTerm $term, int $page, int $per_page = TablePage::PER_PAGE ): TablePage {
		return $this->tables->page( $term, max( 1, $page ), max( 1, $per_page ) );
	}

	/**
	 * Creates a table (R12; Task T2).
	 *
	 * @param string     $name  The name typed.
	 * @param TableStyle $style The tile chosen (Vertical by default).
	 * @throws InvalidField When the name is blank.
	 */
	public function create( string $name, TableStyle $style ): Table {
		return $this->tables->save( Table::draft( $name, $style ) );
	}

	/**
	 * How many tables there are ("Search All <n> Tables").
	 */
	public function count(): int {
		return $this->tables->count();
	}

	/**
	 * Each listed table's Locations count (R11).
	 *
	 * @param TablePage $page The listed tables.
	 * @return array<int, int> By table.
	 */
	public function locations( TablePage $page ): array {
		return $this->locations->counts( array_map( static fn( $table ): int => (int) $table->id, $page->items ) );
	}
}
