<?php
/**
 * The custom fields on one product, in display order (F1, Task 14C).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Fields;

/**
 * The custom fields on one product, in display order (F1 requirements B6). Primary Rating always comes first, as
 * Lasso pins it ("Position in display is fixed."); a rating is kept as Lasso's box keeps it.
 */
final class ProductFields {

	/** Lasso's ID for Primary Rating (Fields::RATING_FIELD_ID). */
	public const RATING_ID = 1;

	/**
	 * The rows, Primary Rating first.
	 *
	 * @var list<ProductField>
	 */
	private array $rows;

	/**
	 * Builds the list; a doubled field keeps its first row, and Primary Rating moves to the front.
	 *
	 * @param list<ProductField> $rows The rows, in order.
	 */
	private function __construct( array $rows ) {
		$kept = array();
		foreach ( $rows as $row ) {
			if ( ! isset( $kept[ $row->field_id ] ) ) {
				$kept[ $row->field_id ] = $row;
			}
		}
		if ( isset( $kept[ self::RATING_ID ] ) ) {
			$kept = array( self::RATING_ID => $kept[ self::RATING_ID ] ) + $kept;
		}
		$this->rows = array_values( $kept );
	}

	/**
	 * The list from storage (`_affibloom_fields`).
	 *
	 * @param array<mixed> $stored Rows as to_array() writes them.
	 */
	public static function from_array( array $stored ): self {
		$rows = array();
		foreach ( $stored as $row ) {
			if ( ! is_array( $row ) || ! is_int( $row['field'] ?? null ) ) {
				continue;
			}
			$rows[] = new ProductField(
				$row['field'],
				is_string( $row['value'] ?? null ) ? $row['value'] : '',
				false !== ( $row['visible'] ?? true ),
				true === ( $row['show_name'] ?? false ),
			);
		}

		return new self( $rows );
	}

	/**
	 * The list as stored.
	 *
	 * @return list<array{field: int, value: string, visible: bool, show_name: bool}>
	 */
	public function to_array(): array {
		return array_map(
			static fn( ProductField $row ): array => array(
				'field'     => $row->field_id,
				'value'     => $row->value,
				'visible'   => $row->visible,
				'show_name' => $row->show_name,
			),
			$this->rows
		);
	}

	/**
	 * Every row, in display order.
	 *
	 * @return list<ProductField>
	 */
	public function all(): array {
		return $this->rows;
	}

	/**
	 * The fields' IDs, in display order.
	 *
	 * @return list<int>
	 */
	public function ids(): array {
		return array_map( static fn( ProductField $row ): int => $row->field_id, $this->rows );
	}

	/**
	 * The row for this field, or null.
	 *
	 * @param int $field_id The field's ID.
	 */
	public function find( int $field_id ): ?ProductField {
		foreach ( $this->rows as $row ) {
			if ( $row->field_id === $field_id ) {
				return $row;
			}
		}
		return null;
	}

	/**
	 * The list with a field added at the end, as Lasso's add: the rating starting at 3.5, visible, its name not shown.
	 * A field the product already has stays as it is (Lasso's ON DUPLICATE KEY UPDATE changes nothing).
	 *
	 * @param Field|null $field The field; null (unknown) changes nothing.
	 */
	public function attach( ?Field $field ): self {
		if ( null === $field || null !== $this->find( $field->id ) ) {
			return $this;
		}

		return new self( array_merge( $this->rows, array( new ProductField( $field->id, FieldType::Rating === $field->type ? '3.5' : '' ) ) ) );
	}

	/**
	 * The list without a field.
	 *
	 * @param int $field_id The field's ID.
	 */
	public function detach( int $field_id ): self {
		return new self( array_values( array_filter( $this->rows, static fn( ProductField $row ): bool => $row->field_id !== $field_id ) ) );
	}

	/**
	 * The list as the editor left it: the rows' order, values and toggles (Lasso's lasso_save_field_positions). Only
	 * fields the product has are kept (an update never adds one), each once; a rating is kept as Lasso's box keeps it.
	 *
	 * @param FieldLibrary       $library The fields, for each one's type.
	 * @param list<ProductField> $rows    The rows, in the order shown.
	 */
	public function update( FieldLibrary $library, array $rows ): self {
		$kept = array();
		foreach ( $rows as $row ) {
			$field = $library->find( $row->field_id );
			if ( null === $field || null === $this->find( $row->field_id ) ) {
				continue;
			}
			$value  = FieldType::Rating === $field->type ? self::rating( $row->value ) : $row->value;
			$kept[] = new ProductField( $row->field_id, $value, $row->visible, self::RATING_ID === $row->field_id && $row->show_name );
		}

		return new self( $kept );
	}

	/**
	 * A rating as Lasso's box keeps it (url-details.php 906-916): 0, nothing or not a number is 1; above 5 is 5; one
	 * decimal.
	 *
	 * @param string $typed What was typed.
	 */
	private static function rating( string $typed ): string {
		$stars = is_numeric( trim( $typed ) ) ? (float) trim( $typed ) : 0.0;
		$stars = 0.0 >= $stars ? 1.0 : min( 5.0, $stars );

		return number_format( floor( $stars * 10 + 0.5 ) / 10, 1, '.', '' );
	}
}
