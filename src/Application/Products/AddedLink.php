<?php
/**
 * What "Add A New Link" did with one link: the product, and whether the site already had it.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Products;

use Affibloom\Domain\Product\Product;

/**
 * What "Add A New Link" did with one link: the product, and whether the site already had it.
 */
final class AddedLink {

	/**
	 * Builds the result.
	 *
	 * @param Product $product         The new product, or the existing one with the same link.
	 * @param bool    $already_existed True when nothing was created.
	 */
	public function __construct( public readonly Product $product, public readonly bool $already_existed ) {}
}
