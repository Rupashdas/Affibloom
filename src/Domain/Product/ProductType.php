<?php
/**
 * What kind of merchant a product's link goes to. Task 12 adds Amazon.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Product;

/**
 * What kind of merchant a product's link goes to. Task 12 adds Amazon.
 */
enum ProductType: string {
	case Other = 'other';
}
