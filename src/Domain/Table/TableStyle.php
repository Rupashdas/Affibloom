<?php
/**
 * How a comparison table lays its products out.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Table;

/**
 * Lasso's table style (F25 R1, R7, R12): `Row`, products across rows ("Horizontal" in the list); `Column`, products in
 * columns ("Vertical", the default of Create Table).
 */
enum TableStyle: string {

	case Row    = 'row';
	case Column = 'column';

	/**
	 * The style a stored value names; Lasso's `Row` and `Column` are read in any case, anything else is Column.
	 *
	 * @param string $value The stored value.
	 */
	public static function from_stored( string $value ): self {
		return 'row' === strtolower( trim( $value ) ) ? self::Row : self::Column;
	}
}
