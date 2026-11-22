<?php
/**
 * What the extension API may do with fields.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Extension;

use Affibloom\Application\Fields\FieldsService;
use Affibloom\Domain\Fields\Field;
use Affibloom\Domain\Fields\ProductField;
use Affibloom\Domain\InvalidField;
use InvalidArgumentException;

/**
 * What the extension API may do with fields (Task 26E): read the definitions, create one, rename a field, and read or
 * set a product's field rows, all through the Fields screens' and the product page's own rules.
 */
final class FieldAccess {

	/**
	 * Builds the access.
	 *
	 * @param FieldsService $fields The Fields screens' and the product page's rules.
	 */
	public function __construct( private FieldsService $fields ) {}

	/**
	 * Every field: the built-in ones (with Lasso's IDs), then the created ones.
	 *
	 * @return list<array{id: int, name: string, type: string, description: string, built_in: bool}>
	 */
	public function definitions(): array {
		return array_map(
			static fn ( Field $field ): array => array(
				'id'          => $field->id,
				'name'        => $field->name,
				'type'        => $field->type->value,
				'description' => $field->description,
				'built_in'    => $field->built_in,
			),
			$this->fields->all()->all()
		);
	}

	/**
	 * Creates a field.
	 *
	 * @param string $name        Its name.
	 * @param string $type        Its kind (`text`, `textarea`, `rating`, …).
	 * @param string $description Its description.
	 * @return int Its ID.
	 * @throws InvalidArgumentException When a value is refused.
	 */
	public function create( string $name, string $type, string $description ): int {
		try {
			return $this->fields->create( $name, $type, $description )->id;
		} catch ( InvalidField $refused ) {
			throw new InvalidArgumentException( esc_html( $refused->field . ': ' . $refused->reason ) );
		}
	}

	/**
	 * Renames a field, its kind and description kept (a built-in field takes a name only).
	 *
	 * @param int    $id   The field.
	 * @param string $name The name.
	 * @throws InvalidArgumentException When there is no such field or the name is refused.
	 */
	public function rename( int $id, string $name ): void {
		$field = $this->fields->get( $id );
		if ( null === $field ) {
			throw new InvalidArgumentException( esc_html( "No field $id." ) );
		}
		try {
			$this->fields->edit( $id, $name, $field->type->value, $field->description );
		} catch ( InvalidField $refused ) {
			throw new InvalidArgumentException( esc_html( $refused->field . ': ' . $refused->reason ) );
		}
	}

	/**
	 * A published product's field rows in display order, or null.
	 *
	 * @param int $product The product.
	 * @return list<array{field: int, value: string, visible: bool, show_name: bool}>|null
	 */
	public function rows( int $product ): ?array {
		$rows = $this->fields->fields( $product );

		return null === $rows ? null : $rows->to_array();
	}

	/**
	 * Sets a published product's field rows, in this order, as the product page's Fields do: the fields are attached
	 * first, and rows not given are taken off.
	 *
	 * @param int                                                                      $product The product.
	 * @param list<array{field: int, value: string, visible?: bool, show_name?: bool}> $rows    The rows.
	 * @throws InvalidArgumentException When the product is not published or a field does not exist.
	 */
	public function set_rows( int $product, array $rows ): void {
		$current = $this->fields->fields( $product );
		if ( null === $current ) {
			throw new InvalidArgumentException( esc_html( "No published product $product." ) );
		}
		try {
			foreach ( $rows as $row ) {
				if ( null === $current->find( (int) $row['field'] ) ) {
					$current = $this->fields->attach( $product, (int) $row['field'] ) ?? $current;
				}
			}
		} catch ( InvalidField $refused ) {
			throw new InvalidArgumentException( esc_html( $refused->field . ': ' . $refused->reason ) );
		}
		$this->fields->update(
			$product,
			array_map( static fn ( array $row ): ProductField => new ProductField( (int) $row['field'], (string) $row['value'], (bool) ( $row['visible'] ?? true ), (bool) ( $row['show_name'] ?? false ) ), $rows )
		);
	}
}
