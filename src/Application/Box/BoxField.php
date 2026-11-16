<?php
/**
 * One custom field in a box.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Box;

/**
 * One custom field of the box's fields block (F5 R23, R30-R34; Lasso's field-row.php): how it prints, its ID and name,
 * and its value as stored. Raw; the partial escapes.
 */
final class BoxField {

	/** Pros (2) or Cons (3): a list with Lasso's check or cross icons. */
	public const PROS_CONS = 'pros-cons';

	/** A rating-type field: stars and the value. */
	public const RATING = 'rating';

	/** A bulleted list. */
	public const BULLETED = 'bulleted';

	/** A numbered list. */
	public const NUMBERED = 'numbered';

	/** Every other type: the value with its line breaks. */
	public const TEXT = 'text';

	/**
	 * Builds a field.
	 *
	 * @param string $kind  One of the constants.
	 * @param int    $id    The field's ID.
	 * @param string $name  The field's name.
	 * @param string $value The product's value, as stored.
	 */
	public function __construct(
		public readonly string $kind,
		public readonly int $id,
		public readonly string $name,
		public readonly string $value,
	) {}

	/**
	 * The value's non-empty lines (R30, R32, R34).
	 *
	 * @return list<string>
	 */
	public function lines(): array {
		return array_values( array_filter( explode( "\n", str_replace( "\r", '', $this->value ) ), static fn( string $line ): bool => '' !== $line ) );
	}
}
