<?php
/**
 * A comparison table as readers see it: its header and its rows.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Table;

/**
 * The reader's table (F25 R46; Task T12; Lasso's table-horizontal.php). Horizontal: a header cell per field group, its
 * fields' names joined " / " with each word capitalised (a field without a name left out); a row per product, a cell
 * per group holding its fields, the badge in the first cell. Every group shows, the heading group first.
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
}
