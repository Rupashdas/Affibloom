<?php
/**
 * Where products are kept.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Product;

use Affibloom\Domain\Fields\ProductFields;

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
	 * The published product with this ID, or null: a missing ID, another post type, a draft or a trashed product
	 * (the product page, Task 14A).
	 *
	 * @param int $id Post ID.
	 */
	public function get( int $id ): ?Product;

	/**
	 * A published product's custom fields, or null when there is no such product (Task 14C).
	 *
	 * @param int $id Post ID.
	 */
	public function fields( int $id ): ?ProductFields;

	/**
	 * Stores a product's custom fields, as each change is made (F1 requirements B6). It does not count as changing the
	 * product (Lasso's field saves leave its record alone), so the Dashboard's order is unchanged.
	 *
	 * @param int           $id     Post ID.
	 * @param ProductFields $fields The fields.
	 */
	public function save_fields( int $id, ProductFields $fields ): void;

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
	 * One page of the published products, most recently changed first, as Lasso orders its Dashboard
	 * (Lasso_DB::get_dashboard_query_without_filter: ORDER BY p.post_modified DESC).
	 *
	 * @param SearchTerm $term      What to look for; an empty term lists everything.
	 * @param LinkKind   $link_kind Lasso's "Link type" filter (Task 13E).
	 * @param int        $page      Page number, from 1.
	 * @param int        $per_page  Products per page.
	 */
	public function page( SearchTerm $term, LinkKind $link_kind, int $page, int $per_page ): ProductPage;

	/**
	 * How many published products the library holds, whatever is being searched for. Lasso counts the same way
	 * for its search box's placeholder ("Search all 263 links…": Lasso_DB::get_dashboard_link_count with no
	 * search).
	 */
	public function count(): int;

	/**
	 * Gives a stored product a new name (its own name to keep it); returns it renamed. Either way it is stored again
	 * and becomes the most recently changed product, as wp_update_post() makes it, so it moves to the top of the
	 * Dashboard: "Add A New Link" relies on that for a link the site already has (Lasso's re-save).
	 *
	 * @param Product $product A stored product.
	 * @param string  $name    Its new name.
	 * @throws \RuntimeException When the storage refuses it.
	 */
	public function rename( Product $product, string $name ): Product;

	/**
	 * Retires a product in favour of another (the repoint, Task 15C): it remembers which product it now points to, so
	 * what draws it by its ID can draw that one instead (Lasso's lasso_repointed_to_lasso_id), and leaves the library
	 * for the trash. No post is touched.
	 *
	 * @param Product $product A stored product.
	 * @param int     $to      The product it points to.
	 * @throws \RuntimeException When the storage refuses it.
	 */
	public function retire( Product $product, int $to ): void;
}
