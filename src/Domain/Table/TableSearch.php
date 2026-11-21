<?php
/**
 * Whether a table matches the Tables list's search.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Table;

use Affibloom\Domain\Product\SearchTerm;

/**
 * Lasso's table search (F25 R8; Task T1): the table's name or one of its products' names holds the cleaned words in
 * order, anything between them (Lasso's LIKE `%a%b%`), in any case.
 */
final class TableSearch {

	/**
	 * Whether the table matches.
	 *
	 * @param SearchTerm   $term          The search; an empty one matches every table.
	 * @param string       $name          The table's name.
	 * @param list<string> $product_names Its products' names.
	 */
	public static function matches( SearchTerm $term, string $name, array $product_names ): bool {
		if ( $term->is_empty() ) {
			return true;
		}
		$words   = preg_split( '/\s+/', $term->text(), -1, PREG_SPLIT_NO_EMPTY );
		$pattern = '/' . implode( '.*', array_map( static fn( string $word ): string => preg_quote( $word, '/' ), is_array( $words ) ? $words : array() ) ) . '/iu';
		foreach ( array_merge( array( $name ), $product_names ) as $text ) {
			if ( 1 === preg_match( $pattern, $text ) ) {
				return true;
			}
		}

		return false;
	}
}
