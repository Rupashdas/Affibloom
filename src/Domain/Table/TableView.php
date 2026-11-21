<?php
/**
 * A comparison table as readers see it: its header and its rows.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Table;

/**
 * The reader's table (F25 R45, R46; Tasks T12-T13; Lasso's table-horizontal.php and table-vertical.php). Horizontal: a
 * header cell per field group, its fields' names joined " / " with each word capitalised (a field without a name left
 * out); a row per product, a cell per group holding its fields, the badge in the first cell. Every group shows, the
 * heading group first. Vertical: no header; each product's cells are its fields one by one (group order, then field
 * order), which the template draws as a row per field and a column per product.
 */
final class TableView {

	/**
	 * Builds the view.
	 *
	 * @param list<string>                                                     $headers The header row's labels.
	 * @param list<array{product: int, badge: string, cells: list<list<int>>}> $rows    A row per product: its cells' fields.
	 */
	public function __construct( public readonly array $headers, public readonly array $rows ) {}

	/**
	 * A Horizontal table's view.
	 *
	 * @param Table              $table The table.
	 * @param array<int, string> $names Each field's name, by ID.
	 */
	public static function horizontal( Table $table, array $names ): self {
		$headers = array();
		foreach ( $table->groups as $group ) {
			$named     = array_filter( array_map( static fn( int $field ): string => $names[ $field ] ?? '', $group['fields'] ), static fn( string $name ): bool => '' !== $name );
			$headers[] = ucwords( implode( ' / ', $named ) );
		}
		$rows = array();
		foreach ( $table->products as $product ) {
			$rows[] = array(
				'product' => $product->id,
				'badge'   => trim( $product->badge ),
				'cells'   => array_column( $table->groups, 'fields' ),
			);
		}

		return new self( $headers, $rows );
	}

	/**
	 * A Vertical table's view (R45; Task T13): each product's fields one cell each.
	 *
	 * @param Table $table The table.
	 */
	public static function vertical( Table $table ): self {
		$rows = array();
		foreach ( $table->products as $product ) {
			$rows[] = array(
				'product' => $product->id,
				'badge'   => trim( $product->badge ),
				'cells'   => array_map( static fn( int $field ): array => array( $field ), $table->field_ids() ),
			);
		}

		return new self( array(), $rows );
	}
}
