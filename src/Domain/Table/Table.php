<?php
/**
 * A comparison table.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Table;

use Affibloom\Domain\InvalidField;

/**
 * A comparison table (F25 R1, R2, D1; Task T1), read and written whole: its name, style, theme, header switches, its
 * products in order with their badges, its field groups (the first is the heading group) and each product's value per
 * field, and the revision a write must match (D4). Later tasks add the behaviour their screens need.
 */
final class Table {

	/**
	 * Builds a table.
	 *
	 * @param int|null                                   $id              Post ID; null until stored.
	 * @param string                                     $name            The table's name.
	 * @param TableStyle                                 $style           Rows or columns.
	 * @param string                                     $theme           Lasso's theme name ("Cactus").
	 * @param list<TableProduct>                         $products        Its products, in order.
	 * @param list<array{id: string, fields: list<int>}> $groups The field groups, the heading group first.
	 * @param array<int, array<int, string>>             $values          Each product's value per field.
	 * @param bool                                       $show_headers    Show Headers (horizontal tables).
	 * @param bool                                       $show_field_name Show the field names.
	 * @param int                                        $revision        Bumped by every write.
	 */
	public function __construct(
		public readonly ?int $id,
		public readonly string $name,
		public readonly TableStyle $style = TableStyle::Column,
		public readonly string $theme = 'Cactus',
		public readonly array $products = array(),
		public readonly array $groups = array(),
		public readonly array $values = array(),
		public readonly bool $show_headers = true,
		public readonly bool $show_field_name = false,
		public readonly int $revision = 0,
	) {}

	/**
	 * A new table from Create Table (R12; Task T2): the name trimmed, the chosen style, Lasso's theme Cactus (its handler
	 * never read the sent one), Show Headers on, Show Field Name on, revision 1.
	 *
	 * @param string     $name  The name typed.
	 * @param TableStyle $style The tile chosen.
	 * @throws InvalidField When the name is blank ('table_name_required').
	 */
	public static function draft( string $name, TableStyle $style ): self {
		$name = trim( $name );
		if ( '' === $name ) {
			throw new InvalidField( 'title', 'table_name_required' );
		}

		return new self( null, $name, $style, 'Cactus', show_headers: true, show_field_name: true, revision: 1 );
	}

	/**
	 * A copy with some properties changed.
	 *
	 * @param array<string, mixed> $changes Property name => new value.
	 */
	public function with( array $changes ): self {
		return new self( ...array_merge( get_object_vars( $this ), $changes ) );
	}

	/**
	 * Its products' IDs, in order.
	 *
	 * @return list<int>
	 */
	public function product_ids(): array {
		return array_map( static fn( TableProduct $product ): int => $product->id, $this->products );
	}
}
