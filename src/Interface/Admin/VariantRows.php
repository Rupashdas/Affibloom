<?php
/**
 * The Variants tab's rows as the templates print them.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Variant\Variant;
use Affibloom\Domain\Variant\VariantKind;
use Affibloom\Domain\Variant\VariantUrl;

/**
 * Each variant's name, key and the address it gives out (F27 R8, R9), under the product's kind now (R23). The tab on
 * load and the save's answer print the same rows (Task V2 took this out of ProductScreen).
 */
final class VariantRows {

	/**
	 * The rows.
	 *
	 * @param Product       $product  The product.
	 * @param list<Variant> $variants Its variants.
	 * @return list<array{id: int, name: string, key: string, url: string}>
	 */
	public static function of( Product $product, array $variants ): array {
		$kind = VariantKind::of( $product );
		$rows = array();
		foreach ( $variants as $variant ) {
			$rows[] = array(
				'id'   => $variant->id,
				'name' => $variant->name,
				'key'  => $variant->key,
				'url'  => VariantUrl::of( $kind, $variant->key, home_url(), $product->url->value() ),
			);
		}

		return $rows;
	}
}
