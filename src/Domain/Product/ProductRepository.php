<?php
/**
 * Where products are kept.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Product;

/**
 * Where products are kept.
 */
interface ProductRepository {

	/**
	 * Stores a new product; returns it with its ID.
	 *
	 * @param Product $product Product to store.
	 * @throws \RuntimeException When the storage refuses it.
	 */
	public function save( Product $product ): Product;

	/**
	 * The published product whose link has this key, or null. Drafts and trashed products are not matches.
	 *
	 * @param string $key From AffiliateUrl::key().
	 */
	public function find_by_url_key( string $key ): ?Product;
}
