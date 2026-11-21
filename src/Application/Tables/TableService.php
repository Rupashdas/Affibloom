<?php
/**
 * The comparison tables' use cases.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Tables;

use Affibloom\Application\Groups\GroupService;
use Affibloom\Domain\Fields\Field;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Domain\Product\SearchTerm;
use Affibloom\Domain\Table\StartingLayout;
use Affibloom\Domain\Table\Table;
use Affibloom\Domain\Table\TableChanged;
use Affibloom\Domain\Table\TableLocations;
use Affibloom\Domain\Table\TablePage;
use Affibloom\Domain\Table\TableRepository;
use Affibloom\Domain\Table\TableRuleBroken;
use Affibloom\Domain\Table\TableStyle;
use Affibloom\Domain\Table\TableTheme;
use Affibloom\Domain\Table\UntitledName;
use Closure;

/**
 * F25's tables (Tasks T1-T17): the list (T1), creating (T2), the builder's settings (T3), its products (T4) and a
 * group's (T5), rows, columns, fields and cell values (T6-T8), their order (T9). Later tasks add the rest. Every builder write carries the table's revision (D4) and moves it on.
 */
final class TableService {

	/**
	 * Builds the service.
	 *
	 * @param TableRepository   $tables    The tables.
	 * @param TableLocations    $locations Where they are used (F21's index).
	 * @param ProductRepository $products  The products a table holds (T4).
	 * @param TableValues       $values    A product's starting values (R22; T4).
	 * @param Closure           $library   The field library: fn(): FieldLibrary (the cells' names and kinds; T4).
	 * @param GroupService      $groups    The groups whose products Add Group brings (T5).
	 */
	public function __construct(
		private TableRepository $tables,
		private TableLocations $locations,
		private ProductRepository $products,
		private TableValues $values,
		private Closure $library,
		private GroupService $groups,
	) {}

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
	 * @throws TableChanged When the table moved on since the editor's copy; InvalidField when the title is blank
	 *                      ('table_title_blank', from settings()).
	 */
	public function update( int $id, int $revision, array $changes ): ?Table {
		return $this->change( $id, $revision, static fn( Table $table ): Table => $table->with( self::settings( $changes ) ) );
	}

	/**
	 * A product added last (R18-R22; Task T4): the first brings the starting layout; each cell starts with the
	 * product's value (TableValues); its badge starts as the product's own badge.
	 *
	 * @param int $id       The table.
	 * @param int $revision The revision the editor's copy has.
	 * @param int $product  The product.
	 * @return Table|null The table as saved; null when it is gone.
	 * @throws TableRuleBroken `product_invalid` (no such product), `product_exists` (R18); TableChanged when the table
	 *                         moved on since the editor's copy.
	 */
	public function add_product( int $id, int $revision, int $product ): ?Table {
		$found = $this->products->get( $product );
		if ( null === $found || null === $found->id ) {
			throw new TableRuleBroken( 'product_invalid' );
		}
		$own = $this->products->fields( $product );

		return $this->change(
			$id,
			$revision,
			fn( Table $table ): Table => $table->add_product(
				$product,
				$found->badge,
				fn( int $field ): string => $this->values->for( $found, $own, $field ),
				self::new_group_id( ... )
			)
		);
	}

	/**
	 * A new field group's ID, as the stored tables have them ("g_" and hex).
	 */
	private static function new_group_id(): string {
		return 'g_' . bin2hex( random_bytes( 6 ) );
	}

	/**
	 * A group's products added last, in the group's order (R24, D6; Task T5): its published products, each as Add
	 * Product adds one; those already in the table are skipped rather than added twice.
	 *
	 * @param int $id       The table.
	 * @param int $revision The revision the editor's copy has.
	 * @param int $group    The group.
	 * @return Table|null The table as saved; null when it is gone.
	 * @throws TableRuleBroken `group_invalid` (no such group); TableChanged when the table moved on since the editor's
	 *                         copy.
	 */
	public function add_group( int $id, int $revision, int $group ): ?Table {
		if ( null === $this->groups->get( $group ) ) {
			throw new TableRuleBroken( 'group_invalid' );
		}
		$members = $this->groups->members( $group );

		return $this->change(
			$id,
			$revision,
			function ( Table $table ) use ( $members ): Table {
				foreach ( $members as $member ) {
					if ( null === $member->id || $table->has_product( $member->id ) ) {
						continue;
					}
					$own   = $this->products->fields( $member->id );
					$table = $table->add_product(
						$member->id,
						$member->badge,
						fn( int $field ): string => $this->values->for( $member, $own, $field ),
						self::new_group_id( ... )
					);
				}

				return $table;
			}
		);
	}

	/**
	 * A product's badge (R21; Task T4).
	 *
	 * @param int    $id       The table.
	 * @param int    $revision The revision the editor's copy has.
	 * @param int    $product  The product.
	 * @param string $badge    The badge, cleaned by the caller.
	 * @return Table|null The table as saved; null when it is gone.
	 * @throws TableChanged When the table moved on since the editor's copy; TableRuleBroken `product_missing` when the
	 *                      product is not in the table.
	 */
	public function set_badge( int $id, int $revision, int $product, string $badge ): ?Table {
		return $this->change( $id, $revision, static fn( Table $table ): Table => $table->set_badge( $product, $badge ) );
	}

	/**
	 * A product and its cells taken out (R23; Task T4).
	 *
	 * @param int $id       The table.
	 * @param int $revision The revision the editor's copy has.
	 * @param int $product  The product.
	 * @return Table|null The table as saved; null when it is gone.
	 * @throws TableChanged When the table moved on since the editor's copy; TableRuleBroken `product_missing` when the
	 *                      product is not in the table.
	 */
	public function remove_product( int $id, int $revision, int $product ): ?Table {
		return $this->change( $id, $revision, static fn( Table $table ): Table => $table->remove_product( $product ) );
	}

	/**
	 * An empty row or column (R25; Task T6).
	 *
	 * @param int $id       The table.
	 * @param int $revision The revision the editor's copy has.
	 * @return Table|null The table as saved; null when it is gone.
	 * @throws TableChanged When the table moved on since the editor's copy; TableRuleBroken `no_products`,
	 *                      `empty_group`.
	 */
	public function add_line( int $id, int $revision ): ?Table {
		return $this->change( $id, $revision, static fn( Table $table ): Table => $table->add_group( self::new_group_id() ) );
	}

	/**
	 * A whole row taken out (R33; Task T6).
	 *
	 * @param int    $id       The table.
	 * @param int    $revision The revision the editor's copy has.
	 * @param string $group    The group.
	 * @return Table|null The table as saved; null when it is gone.
	 * @throws TableChanged When the table moved on since the editor's copy; TableRuleBroken `group_missing`.
	 */
	public function remove_group( int $id, int $revision, string $group ): ?Table {
		return $this->change( $id, $revision, static fn( Table $table ): Table => $table->remove_group( $group ) );
	}

	/**
	 * A field added to a row or column for every product, each filled from its product (R28, R22, D7; Task T6).
	 *
	 * @param int    $id       The table.
	 * @param int    $revision The revision the editor's copy has.
	 * @param string $group    The group.
	 * @param int    $field    The field.
	 * @return Table|null The table as saved; null when it is gone.
	 * @throws TableRuleBroken `field_invalid` (no such field); TableChanged when the table moved on since the editor's
	 *                         copy; the table's own refusals (R28).
	 */
	public function add_field( int $id, int $revision, string $group, int $field ): ?Table {
		if ( null === $this->field( $field ) ) {
			throw new TableRuleBroken( 'field_invalid' );
		}

		return $this->change(
			$id,
			$revision,
			fn( Table $table ): Table => $table->add_field(
				$group,
				$field,
				function ( int $product, int $field ): string {
					$found = $this->products->get( $product );

					return null === $found ? '' : $this->values->for( $found, $this->products->fields( $product ), $field );
				}
			)
		);
	}

	/**
	 * A field taken out of a row or column (R31, D7; Task T6); its values are kept.
	 *
	 * @param int    $id       The table.
	 * @param int    $revision The revision the editor's copy has.
	 * @param string $group    The group.
	 * @param int    $field    The field.
	 * @return Table|null The table as saved; null when it is gone.
	 * @throws TableChanged When the table moved on since the editor's copy; TableRuleBroken `field_missing`.
	 */
	public function remove_field( int $id, int $revision, string $group, int $field ): ?Table {
		return $this->change( $id, $revision, static fn( Table $table ): Table => $table->remove_field( $group, $field ) );
	}

	/**
	 * One cell's value (R30, D11; Tasks T6-T8), cleaned by the caller for the field's kind.
	 *
	 * @param int    $id       The table.
	 * @param int    $revision The revision the editor's copy has.
	 * @param int    $product  The product.
	 * @param int    $field    The field.
	 * @param string $value    The value.
	 * @return Table|null The table as saved; null when it is gone.
	 * @throws TableChanged When the table moved on since the editor's copy; TableRuleBroken `cell_missing`.
	 */
	public function set_value( int $id, int $revision, int $product, int $field, string $value ): ?Table {
		return $this->change( $id, $revision, static fn( Table $table ): Table => $table->set_value( $product, $field, $value ) );
	}

	/**
	 * A button cell's text and address (R35, D11; Task T8). The Primary Button goes to the product's link, so its address
	 * stays the one the cell has; the Secondary's is the one typed. Text is needed, and a Secondary's address too.
	 *
	 * @param int    $id       The table.
	 * @param int    $revision The revision the editor's copy has.
	 * @param int    $product  The product.
	 * @param int    $field    The button field.
	 * @param string $text     The button's text, cleaned by the caller.
	 * @param string $url      The address typed, cleaned by the caller (ignored for the Primary Button).
	 * @return Table|null The table as saved; null when it is gone.
	 * @throws InvalidField `table_button_text` or `table_button_url` (Lasso's words); TableChanged when the table moved
	 *                      on since the editor's copy; TableRuleBroken `cell_missing`.
	 */
	public function set_button( int $id, int $revision, int $product, int $field, string $text, string $url ): ?Table {
		if ( '' === trim( $text ) ) {
			throw new InvalidField( 'button_text', 'table_button_text' );
		}
		$primary = StartingLayout::PRIMARY_BUTTON === $field;
		if ( ! $primary && '' === trim( $url ) ) {
			throw new InvalidField( 'button_url', 'table_button_url' );
		}

		return $this->change(
			$id,
			$revision,
			static function ( Table $table ) use ( $product, $field, $text, $url, $primary ): Table {
				$stored = json_decode( (string) ( $table->values[ $product ][ $field ] ?? '' ), true );
				$link   = $primary ? (string) ( is_array( $stored ) ? ( $stored['url'] ?? '' ) : '' ) : trim( $url );

				return $table->set_value( $product, $field, TableValues::button( trim( $text ), $link ) );
			}
		);
	}

	/**
	 * A new order of the products, the rows or columns, or one cell's fields (R36-R38; Task T9).
	 *
	 * @param int                  $id       The table.
	 * @param int                  $revision The revision the editor's copy has.
	 * @param array<string, mixed> $order    `products` (product IDs), `groups` (group IDs) or `cell` ({group, fields}).
	 * @return Table|null The table as saved; null when it is gone.
	 * @throws TableChanged When the table moved on since the editor's copy; TableRuleBroken `invalid_order`,
	 *                      `group_missing`.
	 */
	public function order( int $id, int $revision, array $order ): ?Table {
		return $this->change(
			$id,
			$revision,
			static function ( Table $table ) use ( $order ): Table {
				if ( isset( $order['products'] ) && is_array( $order['products'] ) ) {
					return $table->order_products( array_values( array_map( 'intval', $order['products'] ) ) );
				}
				if ( isset( $order['groups'] ) && is_array( $order['groups'] ) ) {
					return $table->order_groups( array_values( array_map( 'strval', $order['groups'] ) ) );
				}
				$cell = isset( $order['cell'] ) && is_array( $order['cell'] ) ? $order['cell'] : array();
				if ( isset( $cell['group'], $cell['fields'] ) && is_array( $cell['fields'] ) ) {
					return $table->order_cell( (string) $cell['group'], array_values( array_map( 'intval', $cell['fields'] ) ) );
				}
				throw new TableRuleBroken( 'invalid_order' );
			}
		);
	}

	/**
	 * A field moved to another row or column (R39; Task T9).
	 *
	 * @param int    $id       The table.
	 * @param int    $revision The revision the editor's copy has.
	 * @param string $from     The group it is in.
	 * @param int    $field    The field.
	 * @param string $to       The group it goes to.
	 * @param int    $position Its place there, from 0.
	 * @return Table|null The table as saved; null when it is gone.
	 * @throws TableChanged When the table moved on since the editor's copy; TableRuleBroken `field_exists`,
	 *                      `field_missing`, `group_missing`.
	 */
	public function move_field( int $id, int $revision, string $from, int $field, string $to, int $position ): ?Table {
		return $this->change( $id, $revision, static fn( Table $table ): Table => $table->move_field( $from, $field, $to, $position ) );
	}

	/**
	 * A field of the library, or null (the cell's kind, for cleaning its value).
	 *
	 * @param int $id The field.
	 */
	public function field( int $id ): ?Field {
		return ( $this->library )()->find( $id );
	}

	/**
	 * What the builder draws besides the table (D5): each field the table shows (name, kind), each product's name and,
	 * for an Amazon product, its live price (R29: shown read only; Task T7).
	 *
	 * @param Table $table The table.
	 * @return array{table: Table, fields: array<int, Field>, names: array<int, string>, amazon: array<int, string>}
	 */
	public function builder( Table $table ): array {
		$library = ( $this->library )();
		$fields  = array();
		foreach ( $table->field_ids() as $field ) {
			$found = $library->find( $field );
			if ( null !== $found ) {
				$fields[ $field ] = $found;
			}
		}
		$names  = array();
		$amazon = array();
		foreach ( $table->product_ids() as $product ) {
			$found             = $this->products->get( $product );
			$names[ $product ] = $found->name ?? (string) ( $table->values[ $product ][ StartingLayout::PRODUCT_NAME ] ?? '' );
			if ( null !== $found && '' !== $found->asin ) {
				$amazon[ $product ] = $found->price;
			}
		}

		return array(
			'table'  => $table,
			'fields' => $fields,
			'names'  => $names,
			'amazon' => $amazon,
		);
	}

	/**
	 * One builder write (D4): the stored table, the editor's revision checked, the change made, the revision moved on.
	 *
	 * @param int     $id       The table.
	 * @param int     $revision The revision the editor's copy has.
	 * @param Closure $edit     The change: fn( Table ): Table.
	 * @return Table|null The table as saved; null when it is gone.
	 * @throws TableChanged When the table moved on since the editor's copy. The edit's own refusals (TableRuleBroken,
	 *                      InvalidField) pass through.
	 */
	private function change( int $id, int $revision, Closure $edit ): ?Table {
		$table = $this->tables->get( $id );
		if ( null === $table ) {
			return null;
		}
		if ( $table->revision !== $revision ) {
			throw new TableChanged( 'This table was changed in another window.' );
		}
		$changed = $edit( $table );

		return $this->tables->save( $changed->with( array( 'revision' => $table->revision + 1 ) ) );
	}

	/**
	 * The settings' changes as the table's properties (T3).
	 *
	 * @param array<string, mixed> $changes The settings changed.
	 * @return array<string, mixed>
	 * @throws InvalidField When the title is blank ('table_title_blank').
	 */
	private static function settings( array $changes ): array {
		$with = array();
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

		return $with;
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
