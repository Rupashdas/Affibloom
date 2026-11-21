<?php
/**
 * A comparison table.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Table;

use Affibloom\Domain\InvalidField;
use Closure;

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
	 * Whether a product is in the table.
	 *
	 * @param int $product The product.
	 */
	public function has_product( int $product ): bool {
		return in_array( $product, $this->product_ids(), true );
	}

	/**
	 * Every field the table shows, group by group.
	 *
	 * @return list<int>
	 */
	public function field_ids(): array {
		return array_merge( array(), ...array_map( static fn( array $group ): array => $group['fields'], $this->groups ) );
	}

	/**
	 * A product added last (R18-R20; Task T4). The first product brings the starting layout (R19); every product gets
	 * a value for each of the table's fields (R20, R22).
	 *
	 * @param int     $product The product.
	 * @param string  $badge   Its badge to start with (the product's own badge text, R18).
	 * @param Closure $value   The product's starting value of a field: fn( int $field ): string.
	 * @param Closure $new_id  A new group ID, for the starting layout: fn(): string.
	 * @throws TableRuleBroken `product_exists` when it is in the table already.
	 */
	public function add_product( int $product, string $badge, Closure $value, Closure $new_id ): self {
		if ( $this->has_product( $product ) ) {
			throw new TableRuleBroken( 'product_exists' );
		}
		$groups = array() === $this->groups ? StartingLayout::groups( $new_id ) : $this->groups;
		$values = $this->values;
		foreach ( $this->with( array( 'groups' => $groups ) )->field_ids() as $field ) {
			$values[ $product ][ $field ] = (string) $value( $field );
		}

		return $this->with(
			array(
				'products' => array_merge( $this->products, array( new TableProduct( $product, trim( $badge ) ) ) ),
				'groups'   => $groups,
				'values'   => $values,
			)
		);
	}

	/**
	 * A product and its cells taken out (R23; Task T4). With the last product gone the layout goes too, so the next
	 * product brings the starting layout again, as in Lasso, where the groups belong to the products.
	 *
	 * @param int $product The product.
	 * @throws TableRuleBroken `product_missing` when it is not in the table.
	 */
	public function remove_product( int $product ): self {
		if ( ! $this->has_product( $product ) ) {
			throw new TableRuleBroken( 'product_missing' );
		}
		$products = array_values( array_filter( $this->products, static fn( TableProduct $entry ): bool => $entry->id !== $product ) );
		$values   = $this->values;
		unset( $values[ $product ] );

		return $this->with(
			array(
				'products' => $products,
				'groups'   => array() === $products ? array() : $this->groups,
				'values'   => array() === $products ? array() : $values,
			)
		);
	}

	/**
	 * A product's badge (R21; Task T4), trimmed.
	 *
	 * @param int    $product The product.
	 * @param string $badge   The badge.
	 * @throws TableRuleBroken `product_missing` when it is not in the table.
	 */
	public function set_badge( int $product, string $badge ): self {
		if ( ! $this->has_product( $product ) ) {
			throw new TableRuleBroken( 'product_missing' );
		}

		return $this->with(
			array(
				'products' => array_map(
					static fn( TableProduct $entry ): TableProduct => $entry->id === $product ? new TableProduct( $product, trim( $badge ) ) : $entry,
					$this->products
				),
			)
		);
	}

	/**
	 * An empty row (Vertical) or column (Horizontal) for every product, last (R25; Task T6).
	 *
	 * @param string $id The new group's ID.
	 * @throws TableRuleBroken `no_products` before the first product; `empty_group` while an empty one is waiting.
	 */
	public function add_group( string $id ): self {
		if ( array() === $this->products ) {
			throw new TableRuleBroken( 'no_products' );
		}
		foreach ( $this->groups as $group ) {
			if ( array() === $group['fields'] ) {
				throw new TableRuleBroken( 'empty_group' );
			}
		}

		return $this->with(
			array(
				'groups' => array_merge(
					$this->groups,
					array(
						array(
							'id'     => $id,
							'fields' => array(),
						),
					)
				),
			)
		);
	}

	/**
	 * A whole row taken out (R33, Vertical's "Remove same field →"; Task T6): its fields leave every product; their
	 * values stay (D7). The heading group stays.
	 *
	 * @param string $group The group.
	 * @throws TableRuleBroken `group_missing` for the heading group or one not in the table.
	 */
	public function remove_group( string $group ): self {
		$at = $this->group_index( $group );
		if ( $at < 1 ) {
			throw new TableRuleBroken( 'group_missing' );
		}
		$groups = $this->groups;
		array_splice( $groups, $at, 1 );

		return $this->with( array( 'groups' => $groups ) );
	}

	/**
	 * A field added to a group for every product, last in the cell (R28, D7; Task T6). Each product's value is the one
	 * the table kept from before (D7), else its starting value.
	 *
	 * @param string  $group The group.
	 * @param int     $field The field.
	 * @param Closure $value A product's starting value: fn( int $product, int $field ): string.
	 * @throws TableRuleBroken `no_products`, `field_exists` (a field once per table, R2), `group_missing`.
	 */
	public function add_field( string $group, int $field, Closure $value ): self {
		if ( array() === $this->products ) {
			throw new TableRuleBroken( 'no_products' );
		}
		if ( in_array( $field, $this->field_ids(), true ) ) {
			throw new TableRuleBroken( 'field_exists' );
		}
		$at = $this->group_index( $group );
		if ( $at < 0 ) {
			throw new TableRuleBroken( 'group_missing' );
		}
		$groups                    = $this->groups;
		$groups[ $at ]['fields'][] = $field;
		$values                    = $this->values;
		foreach ( $this->product_ids() as $product ) {
			$values[ $product ][ $field ] = $values[ $product ][ $field ] ?? (string) $value( $product, $field );
		}

		return $this->with(
			array(
				'groups' => $groups,
				'values' => $values,
			)
		);
	}

	/**
	 * A field taken out of its group for every product (R31, D7; Task T6). Its values stay, so adding it back brings
	 * them back ("No data is deleted and the Field can always be added back."); a body group left empty goes.
	 *
	 * @param string $group The group.
	 * @param int    $field The field.
	 * @throws TableRuleBroken `field_missing` when the group does not hold it.
	 */
	public function remove_field( string $group, int $field ): self {
		$at = $this->group_index( $group );
		if ( $at < 0 || ! in_array( $field, $this->groups[ $at ]['fields'], true ) ) {
			throw new TableRuleBroken( 'field_missing' );
		}
		$groups                  = $this->groups;
		$groups[ $at ]['fields'] = array_values( array_diff( $groups[ $at ]['fields'], array( $field ) ) );
		if ( $at > 0 && array() === $groups[ $at ]['fields'] ) {
			array_splice( $groups, $at, 1 );
		}

		return $this->with( array( 'groups' => $groups ) );
	}

	/**
	 * One cell's value (R30; Tasks T6-T8), cleaned by the caller.
	 *
	 * @param int    $product The product.
	 * @param int    $field   The field.
	 * @param string $value   The value.
	 * @throws TableRuleBroken `cell_missing` when the product or the field is not in the table.
	 */
	public function set_value( int $product, int $field, string $value ): self {
		if ( ! $this->has_product( $product ) || ! in_array( $field, $this->field_ids(), true ) ) {
			throw new TableRuleBroken( 'cell_missing' );
		}
		$values                       = $this->values;
		$values[ $product ][ $field ] = $value;

		return $this->with( array( 'values' => $values ) );
	}

	/**
	 * The products in a new order (R36; Task T9).
	 *
	 * @param list<int> $order Every product of the table, once each.
	 * @throws TableRuleBroken `invalid_order` when it is not the table's products in some order.
	 */
	public function order_products( array $order ): self {
		self::same_items( $this->product_ids(), $order );
		$by_id = array();
		foreach ( $this->products as $product ) {
			$by_id[ $product->id ] = $product;
		}

		return $this->with( array( 'products' => array_map( static fn( int $id ): TableProduct => $by_id[ $id ], $order ) ) );
	}

	/**
	 * The rows (Vertical) or columns (Horizontal) in a new order (R37; Task T9); the heading group stays first.
	 *
	 * @param list<string> $order Every group of the table, once each (the heading group may be left out).
	 * @throws TableRuleBroken `invalid_order` when it is not the table's groups in some order.
	 */
	public function order_groups( array $order ): self {
		$heading = $this->groups[0]['id'] ?? '';
		$order   = array_merge( array( $heading ), array_values( array_diff( $order, array( $heading ) ) ) );
		self::same_items( array_column( $this->groups, 'id' ), $order );
		$by_id = array_column( $this->groups, null, 'id' );

		return $this->with( array( 'groups' => array_map( static fn( string $id ): array => $by_id[ $id ], $order ) ) );
	}

	/**
	 * A cell's fields in a new order, for every product (R38; Task T9).
	 *
	 * @param string    $group The group.
	 * @param list<int> $order Every field of the group, once each.
	 * @throws TableRuleBroken `group_missing`; `invalid_order` when it is not the group's fields in some order.
	 */
	public function order_cell( string $group, array $order ): self {
		$at = $this->group_index( $group );
		if ( $at < 0 ) {
			throw new TableRuleBroken( 'group_missing' );
		}
		self::same_items( $this->groups[ $at ]['fields'], $order );
		$groups                  = $this->groups;
		$groups[ $at ]['fields'] = $order;

		return $this->with( array( 'groups' => $groups ) );
	}

	/**
	 * A field moved to another row or column, for every product with its values (R39; Task T9); a body group left
	 * empty goes.
	 *
	 * @param string $from     The group it is in.
	 * @param int    $field    The field.
	 * @param string $to       The group it goes to.
	 * @param int    $position Its place there, from 0 (past the end: last).
	 * @throws TableRuleBroken `field_exists` (moved where it is), `field_missing`, `group_missing`.
	 */
	public function move_field( string $from, int $field, string $to, int $position ): self {
		if ( $from === $to ) {
			throw new TableRuleBroken( 'field_exists' );
		}
		if ( $this->group_index( $to ) < 0 ) {
			throw new TableRuleBroken( 'group_missing' );
		}
		$moved  = $this->remove_field( $from, $field );
		$groups = $moved->groups;
		$at     = $moved->group_index( $to );
		array_splice( $groups[ $at ]['fields'], max( 0, min( $position, count( $groups[ $at ]['fields'] ) ) ), 0, array( $field ) );

		return $moved->with( array( 'groups' => $groups ) );
	}

	/**
	 * Refuses an order that is not the same items, each once.
	 *
	 * @param list<int|string> $have  What the table has.
	 * @param list<int|string> $order The order asked for.
	 * @throws TableRuleBroken `invalid_order`.
	 */
	private static function same_items( array $have, array $order ): void {
		$sorted_have  = $have;
		$sorted_order = $order;
		sort( $sorted_have );
		sort( $sorted_order );
		if ( $sorted_have !== $sorted_order ) {
			throw new TableRuleBroken( 'invalid_order' );
		}
	}

	/**
	 * A group's place, from 0 (the heading group); -1 when it is not in the table.
	 *
	 * @param string $group The group.
	 */
	private function group_index( string $group ): int {
		foreach ( $this->groups as $at => $entry ) {
			if ( $entry['id'] === $group ) {
				return (int) $at;
			}
		}

		return -1;
	}

	/**
	 * A copy with everything (R10, D8; Task T11): the products, badges, fields, values and settings, named "<name> -
	 * Copy", not stored yet.
	 *
	 * @param string $suffix The words after the name (" - Copy").
	 */
	public function copy( string $suffix ): self {
		return $this->with(
			array(
				'id'       => null,
				'name'     => $this->name . $suffix,
				'revision' => 1,
			)
		);
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
