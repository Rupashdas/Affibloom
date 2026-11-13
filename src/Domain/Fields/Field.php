<?php
/**
 * A custom field's definition (F1, Task 14C).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Fields;

use Affibloom\Domain\InvalidField;

/**
 * A custom field's definition: Lasso's lasso_fields row (id, name, type, description). Built-in fields are Lasso's
 * own (Primary Rating, Pros, Cons) and keep Lasso's IDs.
 */
final class Field {

	/** Lasso's field_name column is 80 characters. */
	public const NAME_MAX = 80;

	/**
	 * Builds a definition.
	 *
	 * @param int       $id          Lasso's ID for a built-in field; 100 and up for one created here.
	 * @param string    $name        1-80 characters.
	 * @param FieldType $type        The kind of value.
	 * @param string    $description Shown as the field's help and its value's placeholder.
	 * @param bool      $built_in    One of Lasso's own fields.
	 * @throws InvalidField When the name breaks its rule.
	 */
	public function __construct(
		public readonly int $id,
		public readonly string $name,
		public readonly FieldType $type,
		public readonly string $description,
		public readonly bool $built_in = false,
	) {
		if ( '' === trim( $name ) || mb_strlen( $name ) > self::NAME_MAX ) {
			throw new InvalidField( 'name', 'invalid_name' );
		}
	}
}
