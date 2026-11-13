<?php
/**
 * The Dashboard's "Link type" filter.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Product;

/**
 * The Dashboard's "Link type" filter, with Lasso's values (Dashboard_Saved_Views::build_list_extra_where_sql,
 * `dashboard_link_kind`): every product, Amazon's only, or every other merchant's.
 */
enum LinkKind: string {
	case All       = '';
	case Amazon    = 'amazon';
	case NonAmazon = 'non_amazon';

	/**
	 * The filter a request asks for; anything Lasso would not accept means every product.
	 *
	 * @param string $value The request's value.
	 */
	public static function from_input( string $value ): self {
		return self::tryFrom( $value ) ?? self::All;
	}
}
