<?php
/**
 * The placeholder name of a table not yet named.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Table;

use Closure;

/**
 * Lasso's `generate_table_title()` (F25 R14; Task T3): "Untitled Table", or, when a table has that name, "Untitled
 * Table 1", "Untitled Table 2" … the first one free.
 */
final class UntitledName {

	/**
	 * The first free name.
	 *
	 * @param Closure $taken Whether a table has a name: fn( string $name ): bool.
	 */
	public static function next( Closure $taken ): string {
		$name  = 'Untitled Table';
		$index = 0;
		while ( $taken( $name ) ) {
			++$index;
			$name = 'Untitled Table ' . $index;
		}

		return $name;
	}
}
