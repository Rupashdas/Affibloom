<?php
/**
 * A custom field's definition (F1 Task 14C; F26 Task FL1).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Fields;

use Affibloom\Domain\InvalidField;

/**
 * A custom field's definition: Lasso's lasso_fields row (id, name, type, description). Built-in fields are Lasso's
 * own (nine of them, F26 R2) and keep Lasso's IDs.
 */
final class Field {

	/** Lasso's field_name column is 80 characters. */
	public const NAME_MAX = 80;

	/** Lasso's field_description column is 1,000 characters. */
	public const DESCRIPTION_MAX = 1000;

	/**
	 * Builds a definition.
	 *
	 * @param int       $id          Lasso's ID for a built-in field; 100 and up for one created here.
	 * @param string    $name        1-80 characters.
	 * @param FieldType $type        The kind of value.
	 * @param string    $description Shown as the field's help and its value's placeholder; at most 1,000 characters.
	 * @param bool      $built_in    One of Lasso's own fields.
	 * @param bool      $table_only  A built-in field comparison tables fill (Description, Product name, Product Image,
	 *                               both buttons, Price): listed on the Fields screens, left out of a product's library.
	 * @throws InvalidField When the name or the description breaks its rule.
	 */
	public function __construct(
		public readonly int $id,
		public readonly string $name,
		public readonly FieldType $type,
		public readonly string $description,
		public readonly bool $built_in = false,
		public readonly bool $table_only = false,
	) {
		if ( '' === trim( $name ) ) {
			throw new InvalidField( 'name', 'field_title_required' );
		}
		if ( mb_strlen( $name ) > self::NAME_MAX ) {
			throw new InvalidField( 'name', 'invalid_name' );
		}
		if ( mb_strlen( $description ) > self::DESCRIPTION_MAX ) {
			throw new InvalidField( 'description', 'field_description_long' );
		}
	}

	/**
	 * The same field under another name (a built-in's title is the one thing a save changes, F26 D4).
	 *
	 * @param string $name The new name, 1-80 characters.
	 * @throws InvalidField When the name breaks its rule.
	 */
	public function renamed( string $name ): self {
		return new self( $this->id, trim( $name ), $this->type, $this->description, $this->built_in, $this->table_only );
	}
}
