<?php
/**
 * Where products are kept.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Product;

use Affibloom\Domain\Fields\ProductFields;
use Affibloom\Domain\LinkHealth\AlertListing;

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
	 * The published product whose link, without its query string, has this key (PlainUrlKey; Task 9C, the way Lasso's
	 * `url_without_arguments` finds the same page behind two different links). Only products stored with a plain key
	 * can be found; Amazon products never have one.
	 *
	 * @param string $key From PlainUrlKey::of(), never empty.
	 */
	public function find_by_plain_key( string $key ): ?Product;

	/**
	 * Sets one of the link switches (`new_tab`, `nofollow`, `sponsored`, `show_disclosure`, `cloak`,
	 * `detect_opportunities`) on every product, whatever
	 * its status, adding it where it is missing (Task 9D; Lasso's lasso_override_display_option).
	 *
	 * @param string $field The product field.
	 * @param bool   $value The new value.
	 * @return int How many products were given the value.
	 * @throws \InvalidArgumentException For a field that is not one of the four.
	 */
	public function set_flag_for_all( string $field, bool $value ): int;

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
	 * The IDs of published products whose Primary URL is a geni.us link, highest first, below an ID (the geni.us
	 * clean-up job, C1P; Lasso's get_genius_links: lasso_custom_redirect LIKE '%geni.us%', lasso_id DESC).
	 *
	 * @param int $before Only IDs below this one; 0 for no limit.
	 * @param int $limit  At most this many.
	 * @return list<int>
	 */
	public function genius_ids( int $before, int $limit ): array;

	/**
	 * One page of the published products, most recently changed first, as Lasso orders its Dashboard
	 * (Lasso_DB::get_dashboard_query_without_filter: ORDER BY p.post_modified DESC), the higher ID first on a tie
	 * (F19 D9).
	 *
	 * @param SearchTerm        $term      What to look for; an empty term lists everything.
	 * @param LinkKind          $link_kind Lasso's "Link type" filter (Task 13E).
	 * @param int               $page      Page number, from 1.
	 * @param int               $per_page  Products per page.
	 * @param int               $group     The Group filter (F23 R22): 0 for all, -1 for products in no group, else a group's ID.
	 * @param int               $post_id   One product only (F19 R30, Task 13F): 0 for all.
	 * @param AlertListing|null $alerts The Alert sort and the alert tab (F11, Tasks 31A, 31B); null for neither.
	 * @param Placement         $placement The Placements filter (F12 L13, Task 36D).
	 * @param ListOrder         $order     The header sort (F19 R1-R6, D8, D9; Task 13K).
	 * @param TableFilter       $tables    The Comparison table filter (F25 R59, Task T16).
	 */
	public function page( SearchTerm $term, LinkKind $link_kind, int $page, int $per_page, int $group = 0, int $post_id = 0, ?AlertListing $alerts = null, Placement $placement = Placement::Any, ListOrder $order = new ListOrder(), TableFilter $tables = TableFilter::Any ): ProductPage;

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
	 * Sets a stored product's image link alone (Refresh, C1D), clearing its media library item, without making it the
	 * most recently changed.
	 *
	 * @param Product $product A stored product.
	 * @param string  $url     The image link.
	 */
	public function set_image( Product $product, string $url ): Product;

	/**
	 * Sets a stored product's feature bullets alone (Refresh and bulk sync, C1Q4), without making it the most recently
	 * changed.
	 *
	 * @param Product      $product  A stored product.
	 * @param list<string> $features The bullets.
	 */
	public function set_features( Product $product, array $features ): Product;

	/**
	 * Retires a product in favour of another (the repoint, Task 15C): it remembers which product it now points to, so
	 * what draws it by its ID can draw that one instead (Lasso's lasso_repointed_to_lasso_id), and leaves the library
	 * as retired (its own status, Lasso's lasso_delete; Task 17C), never emptied away like the trash. No post is touched.
	 *
	 * @param Product $product A stored product.
	 * @param int     $to      The product it points to.
	 * @throws \RuntimeException When the storage refuses it.
	 */
	public function retire( Product $product, int $to ): void;

	/**
	 * The product a product was repointed to, whatever its status (Lasso's front end reads
	 * lasso_repointed_to_lasso_id so), for the box's resolver (Task 18A).
	 *
	 * @param int $id A product's ID.
	 * @return int|null The target's ID, 0 when it points nowhere, null when no product with this ID exists.
	 */
	public function repointed_to( int $id ): ?int;

	/**
	 * The published or retired product with this slug, or null (F24 D3: the cloaked address of a retired product still
	 * answers, as Lasso's lasso_delete links do).
	 *
	 * @param string $slug A slug.
	 */
	public function find_by_slug( string $slug ): ?Product;

	/**
	 * The published or retired product that had this slug before, or null (F24 N1: old slugs keep answering; Task CL10).
	 * The one changed last wins when two had it.
	 *
	 * @param string $slug A slug.
	 */
	public function find_by_old_slug( string $slug ): ?Product;

	/**
	 * The ID of the product with this slug, whatever its status (F31 A2: Lasso looks a slug up so; the resolver then
	 * decides what draws), or null.
	 *
	 * @param string $slug A product's slug.
	 */
	public function id_by_slug( string $slug ): ?int;

	/**
	 * Deletes a stored product for good, with everything stored on it (the product page's delete, Task 17A).
	 *
	 * @param int $id A stored product's ID.
	 * @return bool False when the storage refused it.
	 */
	public function delete( int $id ): bool;

	/**
	 * Moves a stored product to the trash, where nothing lists it; it keeps everything stored on it (the Dashboard's
	 * delete, Task 17B).
	 *
	 * @param int $id A stored product's ID.
	 * @return bool False when the storage refused it.
	 */
	public function trash( int $id ): bool;

	/**
	 * The trashed or retired product a link belongs to, or null (Task 17D; Lasso's is_lasso_url_exist matches records of
	 * any status). The arms are the published lookups' (a link's key, an Amazon ASIN on its store, an Amazon search key),
	 * tried in that order; within an arm the newest product wins. An empty key or ASIN skips its arm.
	 *
	 * @param list<string> $url_keys   Keys from AffiliateUrl::key(): the link as typed, where it leads, as it is stored.
	 * @param string       $asin       An Amazon link's ASIN, or ''.
	 * @param string       $country    Its store's country code.
	 * @param string       $search_key An Amazon search page's key, or ''.
	 */
	public function find_deleted( array $url_keys, string $asin, string $country, string $search_key ): ?Product;

	/**
	 * Publishes a trashed or retired product again, as Lasso's add flow does with wp_update_post( publish ); a retired
	 * one loses its pointer, so its boxes show it again (F1 D42). It keeps everything else stored on it.
	 *
	 * @param Product $product A trashed or retired product.
	 * @throws \RuntimeException When the storage refuses it.
	 */
	public function revive( Product $product ): Product;

	/**
	 * Whether a retired product (F1's repoint) has this ASIN on this store: the Amazon import leaves it alone (F35 R25;
	 * Task AM1).
	 *
	 * @param string $asin    The ASIN.
	 * @param string $country The store's country code.
	 */
	public function is_retired_asin( string $asin, string $country ): bool;
}
