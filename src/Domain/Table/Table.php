<?php
/**
 * A comparison table.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Table;

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
	 * Its products' IDs, in order.
	 *
	 * @return list<int>
	 */
	public function product_ids(): array {
		return array_map( static fn( TableProduct $product ): int => $product->id, $this->products );
	}
}
