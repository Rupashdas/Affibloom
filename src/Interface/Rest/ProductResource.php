<?php
/**
 * Products as REST arrays.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Domain\Product\Product;

/**
 * Products as REST arrays.
 */
final class ProductResource {

	/**
	 * The short form returned after adding.
	 *
	 * @param Product $product A saved product.
	 * @return array{id: int, name: string, url: string}
	 */
	public function summary( Product $product ): array {
		return array(
			'id'   => (int) $product->id,
			'name' => $product->name,
			'url'  => $product->url->value(),
		);
	}
}
