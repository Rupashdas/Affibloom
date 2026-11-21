<?php
/**
 * The Dashboard's "Comparison table" filter.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Product;

/**
 * The Dashboard's "Comparison table" filter, with Lasso's values (dashboard.php 182-189, saved views'
 * `dashboard_comparison_table`; F25 R59, Task T16): every product, the products in at least one comparison table, or
 * those in none.
 */
enum TableFilter: string {
	case Any        = '';
	case InTable    = 'in_table';
	case NotInTable = 'not_in_table';

	/**
	 * The filter a request asks for; anything else means every product.
	 *
	 * @param string $value The request's value.
	 */
	public static function from_input( string $value ): self {
		return self::tryFrom( $value ) ?? self::Any;
	}
}
