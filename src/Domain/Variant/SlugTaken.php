<?php
/**
 * Who else answers at a slug.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Variant;

/**
 * What a permalink variant's key may clash with (F27 R15, D7): another product's variant, a post or page, another
 * product's slug or old slug.
 */
interface SlugTaken {

	/**
	 * Whether another product has a permalink variant with this key.
	 *
	 * @param string $key    The key.
	 * @param int    $product_id The product whose variants are saved.
	 */
	public function by_other_variant( string $key, int $product_id ): bool;

	/**
	 * Whether a post or a page, published or a draft, has this slug.
	 *
	 * @param string $key The key.
	 */
	public function by_post( string $key ): bool;

	/**
	 * Whether another product has this slug, now or before.
	 *
	 * @param string $key    The key.
	 * @param int    $product_id The product whose variants are saved.
	 */
	public function by_other_product( string $key, int $product_id ): bool;
}
