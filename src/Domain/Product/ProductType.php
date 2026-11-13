<?php
/**
 * What kind of merchant a product's link goes to.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Product;

/**
 * What kind of merchant a product's link goes to. Amazon is any link to one of Lasso's Amazon hosts (its
 * url_details.product_type "amazon"); with an ASIN it is what Lasso calls an "Amazon Product".
 */
enum ProductType: string {
	case Other  = 'other';
	case Amazon = 'amazon';
}
