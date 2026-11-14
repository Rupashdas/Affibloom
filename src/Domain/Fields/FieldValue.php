<?php
/**
 * A product's field value as Lasso stores it (F26, Task FL5).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Fields;

use Affibloom\Domain\InvalidField;

/**
 * A product's field value as Lasso stores it: lasso_save_field_positions trims each line and drops the blank ones
 * (F26 R26, D10), and lasso_field_mapping's value holds 1,000 characters (R5, D9).
 */
final class FieldValue {

	/** The longest value, in characters. */
	public const MAX = 1000;

	/**
	 * The value with each line trimmed and blank lines dropped.
	 *
	 * @param string $typed What was typed.
	 */
	public static function clean( string $typed ): string {
		$lines = preg_split( '/\r\n|\r|\n/', $typed );
		$kept  = array_filter( array_map( 'trim', false === $lines ? array( $typed ) : $lines ), static fn( string $line ): bool => '' !== $line );

		return implode( "\n", $kept );
	}

	/**
	 * The value cleaned, if it fits.
	 *
	 * @param int    $field_id The field's ID, which names the row in the refusal ("rows.<id>").
	 * @param string $typed    What was typed.
	 * @throws InvalidField When the cleaned value is longer than 1,000 characters.
	 */
	public static function accepted( int $field_id, string $typed ): string {
		$value = self::clean( $typed );
		if ( mb_strlen( $value ) > self::MAX ) {
			throw new InvalidField( 'rows.' . $field_id, 'field_value_long' );
		}

		return $value;
	}
}
