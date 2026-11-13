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

	/**
	 * The published Amazon product with this ASIN on this store, or null (Lasso_DB::get_lasso_id_by_product_id_and_type).
	 * The ASIN is compared without case, as Lasso's MySQL lookup does.
	 *
	 * @param string $asin    The ASIN.
	 * @param string $country The store's country code.
	 */
	public function find_amazon( string $asin, string $country ): ?Product;

	/**
	 * The published product whose Amazon search has this key, or null (Lasso_DB::get_url_details_by_search_key),
	 * compared without case as Lasso's MySQL columns are.
	 *
	 * @param string $key From AmazonUrl::search_key().
	 */
	public function find_by_search_key( string $key ): ?Product;

	/**
	 * Gives a stored product a new name; returns it renamed.
	 *
	 * @param Product $product A stored product.
	 * @param string  $name    Its new name.
	 * @throws \RuntimeException When the storage refuses it.
	 */
	public function rename( Product $product, string $name ): Product;
}
