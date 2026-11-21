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
	 * Its products' IDs, in order.
	 *
	 * @return list<int>
	 */
	public function product_ids(): array {
		return array_map( static fn( TableProduct $product ): int => $product->id, $this->products );
	}
}
