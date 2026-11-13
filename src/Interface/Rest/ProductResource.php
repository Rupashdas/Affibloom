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

	/**
	 * Every field as stored, for the product page to show what was saved (F1 requirements 9 S15) and for quick edit.
	 *
	 * @param Product $product A saved product.
	 * @return array<string, mixed>
	 */
	public function full( Product $product ): array {
		return $this->summary( $product ) + array(
			'type'             => $product->type->value,
			'asin'             => $product->asin,
			'button_text'      => $product->button_text,
			'new_tab'          => $product->new_tab,
			'nofollow'         => $product->nofollow,
			'buttons'          => $product->buttons->to_array(),
			'primary_position' => $product->buttons->main_position(),
			'theme'            => null === $product->theme ? '' : $product->theme->name(),
			'image_url'        => $product->image_url,
			'image_id'         => $product->image_id,
			'badge'            => $product->badge,
			'price'            => $product->price,
			'show_price'       => $product->show_price,
			'description'      => $product->description,
			'disclosure'       => $product->disclosure,
			'sponsored'        => $product->sponsored,
			'show_disclosure'  => $product->show_disclosure,
			'prime'            => $product->prime,
		);
	}
}
