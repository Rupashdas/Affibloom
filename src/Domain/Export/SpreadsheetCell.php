<?php
/**
 * A CSV cell a spreadsheet will not run as a formula.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Export;

/**
 * Lasso's formula guard (`sanitize_spreadsheet_cell`; F11 R28, F22 R61): a cell whose text, after leading spaces, starts
 * with `=`, `+`, `@`, or a `-` not followed by a digit gets a leading tab, so a spreadsheet shows it as text. Taken out
 * of the broken-links export on its second use (Task O9), with Lasso's leading-space rule it had missed.
 */
final class SpreadsheetCell {

	/**
	 * The cell, guarded.
	 *
	 * @param string $cell The cell.
	 */
	public static function guard( string $cell ): string {
		return 1 === preg_match( '/^(?:[=+@]|-(?!\d))/', ltrim( $cell ) ) ? "\t" . $cell : $cell;
	}
}
