<?php
/**
 * The Dashboard's "Placements" filter.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Product;

/**
 * The Dashboard's "Placements" filter, with Lasso's values (dashboard.php 174-181, saved views' `dashboard_placements`;
 * F12 L13, Task 36D): every product, the products used somewhere (by the Locations count's rule, L1), or those used
 * nowhere.
 */
enum Placement: string {
	case Any          = '';
	case InContent    = 'has_placements';
	case NotInContent = 'no_placements';

	/**
	 * The filter a request asks for; anything else means every product.
	 *
	 * @param string $value The request's value.
	 */
	public static function from_input( string $value ): self {
		return self::tryFrom( $value ) ?? self::Any;
	}
}
