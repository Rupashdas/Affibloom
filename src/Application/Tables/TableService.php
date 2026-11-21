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
use Affibloom\Domain\Table\TableChanged;
use Affibloom\Domain\Table\TableLocations;
use Affibloom\Domain\Table\TablePage;
use Affibloom\Domain\Table\TableRepository;
use Affibloom\Domain\Table\TableStyle;
use Affibloom\Domain\Table\TableTheme;
use Affibloom\Domain\Table\UntitledName;

/**
 * F25's tables (Tasks T1-T17): the list (T1), creating (T2), the builder's settings (T3). Later tasks add the rest.
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
	 * One table, or null (Task T3).
	 *
	 * @param int $id The table.
	 */
	public function get( int $id ): ?Table {
		return $this->tables->get( $id );
	}

	/**
	 * The builder's settings saved at once (R14, R15, D4; Task T3): any of title, style, theme, show_headers,
	 * show_field_name. The write must carry the stored revision; a blank title is refused; the revision moves on.
	 *
	 * @param int                  $id       The table.
	 * @param int                  $revision The revision the editor's copy has.
	 * @param array<string, mixed> $changes  The settings changed.
	 * @return Table|null The table as saved; null when it is gone.
	 * @throws TableChanged When the table moved on since the editor's copy.
	 * @throws InvalidField When the title is blank ('table_title_blank').
	 */
	public function update( int $id, int $revision, array $changes ): ?Table {
		$table = $this->tables->get( $id );
		if ( null === $table ) {
			return null;
		}
		if ( $table->revision !== $revision ) {
			throw new TableChanged( 'This table was changed in another window.' );
		}
		$with = array( 'revision' => $table->revision + 1 );
		if ( array_key_exists( 'title', $changes ) ) {
			$with['name'] = trim( (string) $changes['title'] );
			if ( '' === $with['name'] ) {
				throw new InvalidField( 'title', 'table_title_blank' );
			}
		}
		if ( array_key_exists( 'style', $changes ) ) {
			$with['style'] = TableStyle::from_stored( (string) $changes['style'] );
		}
		if ( array_key_exists( 'theme', $changes ) ) {
			$with['theme'] = TableTheme::from_stored( (string) $changes['theme'] );
		}
		foreach ( array( 'show_headers', 'show_field_name' ) as $toggle ) {
			if ( array_key_exists( $toggle, $changes ) ) {
				$with[ $toggle ] = (bool) $changes[ $toggle ];
			}
		}

		return $this->tables->save( $table->with( $with ) );
	}

	/**
	 * The name field's placeholder for a table not yet named (R14).
	 */
	public function untitled(): string {
		return UntitledName::next( fn( string $name ): bool => $this->tables->name_taken( $name ) );
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
