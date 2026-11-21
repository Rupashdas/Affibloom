<?php
/**
 * A comparison table's theme.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Table;

/**
 * Lasso's eight table themes (F25 R14; Task T3; `class-setting-enum.php` 29-52), by the names it stores and shows.
 */
final class TableTheme {

	/** The themes, in Lasso's order. */
	public const ALL = array( 'Cactus', 'Cutter', 'Flow', 'Geek', 'Lab', 'Llama', 'Money', 'Splash' );

	/** A new table's theme (R12). */
	public const DEFAULT = 'Cactus';

	/**
	 * The theme a stored value names, in any case; anything else is the default.
	 *
	 * @param string $value The stored value.
	 */
	public static function from_stored( string $value ): string {
		foreach ( self::ALL as $theme ) {
			if ( 0 === strcasecmp( $theme, trim( $value ) ) ) {
				return $theme;
			}
		}

		return self::DEFAULT;
	}
}
