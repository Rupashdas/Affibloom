<?php
/**
 * What the extension API may do with comparison tables.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Extension;

use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Domain\Table\Table;
use Affibloom\Domain\Table\TableProduct;
use Affibloom\Domain\Table\TableRepository;
use Affibloom\Domain\Table\TableStyle;
use Affibloom\Domain\Table\TableTheme;
use Closure;
use InvalidArgumentException;

/**
 * What the extension API may do with comparison tables (Task 26I): read a table whole, and store another plugin's table
 * whole: its name, style, theme and switches, its products in order with their badges, its field groups (the heading
 * group first) and each product's value per field, as the builder stores them (a button cell is its JSON). The name
 * may not be blank and every product and field must exist.
 */
final class TableAccess {

	/**
	 * Builds the access.
	 *
	 * @param TableRepository   $tables   The tables.
	 * @param Closure           $field    Whether a field exists in the builder's library: fn( int $id ): bool.
	 * @param ProductRepository $products The products.
	 */
	public function __construct( private TableRepository $tables, private Closure $field, private ProductRepository $products ) {}

	/**
	 * A table whole, or null when there is none.
	 *
	 * @param int $id The table.
	 * @return array{name: string, style: string, theme: string, show_headers: bool, show_field_name: bool, products: list<array{id: int, badge: string}>, groups: list<array{id: string, fields: list<int>}>, values: array<int, array<int, string>>}|null
	 */
	public function get( int $id ): ?array {
		$table = $this->tables->get( $id );

		return null === $table ? null : array(
			'name'            => $table->name,
			'style'           => $table->style->value,
			'theme'           => $table->theme,
			'show_headers'    => $table->show_headers,
			'show_field_name' => $table->show_field_name,
			'products'        => array_map(
				static fn ( TableProduct $product ): array => array(
					'id'    => $product->id,
					'badge' => $product->badge,
				),
				$table->products
			),
			'groups'          => $table->groups,
			'values'          => $table->values,
		);
	}

	/**
	 * Stores a table whole: a new one when $id is 0, else in place of that table.
	 *
	 * @param int                  $id   The table to replace; 0 for a new one.
	 * @param array<string, mixed> $data The keys get() gives.
	 * @return int The table's ID.
	 * @throws InvalidArgumentException When the name is blank, a product or a field does not exist, or the table to
	 *                                  replace is gone.
	 */
	public function put( int $id, array $data ): int {
		$name = trim( (string) ( $data['name'] ?? '' ) );
		if ( '' === $name ) {
			throw new InvalidArgumentException( 'name: table_title_blank' );
		}
		$products = array();
		foreach ( (array) ( $data['products'] ?? array() ) as $row ) {
			$product = (int) ( $row['id'] ?? 0 );
			if ( null === $this->products->get( $product ) ) {
				throw new InvalidArgumentException( esc_html( "products: product_invalid ($product)" ) );
			}
			$products[] = new TableProduct( $product, trim( (string) ( $row['badge'] ?? '' ) ) );
		}
		$groups = array();
		foreach ( (array) ( $data['groups'] ?? array() ) as $group ) {
			$fields = array_map( 'intval', (array) ( $group['fields'] ?? array() ) );
			foreach ( $fields as $field ) {
				if ( ! ( $this->field )( $field ) ) {
					throw new InvalidArgumentException( esc_html( "groups: field_invalid ($field)" ) );
				}
			}
			$groups[] = array(
				'id'     => (string) ( $group['id'] ?? '' ),
				'fields' => $fields,
			);
		}
		$values = array();
		foreach ( (array) ( $data['values'] ?? array() ) as $product => $cells ) {
			foreach ( (array) $cells as $field => $value ) {
				$values[ (int) $product ][ (int) $field ] = (string) $value;
			}
		}
		$before = 0 === $id ? null : $this->tables->get( $id );
		if ( 0 !== $id && null === $before ) {
			throw new InvalidArgumentException( esc_html( "No table $id." ) );
		}

		return (int) $this->tables->save(
			new Table(
				0 === $id ? null : $id,
				$name,
				TableStyle::from_stored( (string) ( $data['style'] ?? '' ) ),
				TableTheme::from_stored( (string) ( $data['theme'] ?? '' ) ),
				$products,
				$groups,
				$values,
				(bool) ( $data['show_headers'] ?? true ),
				(bool) ( $data['show_field_name'] ?? false ),
				null === $before ? 0 : $before->revision + 1
			)
		)->id;
	}
}
