<?php
/**
 * Products as affibloom_product posts with _affibloom_* meta.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Domain\Display\Theme;
use Affibloom\Domain\Fields\ProductFields;
use Affibloom\Domain\LinkHealth\AlertListing;
use Affibloom\Application\Clicks\ClickReport;
use Affibloom\Domain\LinkHealth\IssueType;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Buttons;
use Affibloom\Domain\Product\LinkKind;
use Affibloom\Domain\Opportunity\BaseDomain;
use Affibloom\Domain\Product\ListOrder;
use Affibloom\Domain\Product\Placement;
use Affibloom\Domain\Product\PlainUrlKey;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductPage;
use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Domain\Product\SearchTerm;
use Affibloom\Domain\Product\TableFilter;
use InvalidArgumentException;
use RuntimeException;
use WP_Post;
use WP_Query;

/**
 * Products as affibloom_product posts with _affibloom_* meta (docs/spec/interface.md, section 4).
 */
final class PostTypeProductRepository implements ProductRepository {

	/** The product fields that are switches on the primary link, which a changed link default can be pushed to (Task 9D). */
	private const LINK_SWITCHES = array( 'new_tab', 'nofollow', 'sponsored', 'show_disclosure', 'cloak', 'detect_opportunities' );

	/** The query variable that carries the search to the WHERE filter, and nothing else. */
	private const SEARCH_VAR = 'affibloom_search';

	/** The query variable that marks the Dashboard's queries the Alert sort and tab change (alert_clauses()). */
	private const ALERTS_VAR = 'affibloom_alerts';

	/** The query var that carries the Placements filter (Task 36D). */
	private const PLACEMENT_VAR = 'affibloom_placement';

	/** The query var that carries the Locations or Clicks sort (Task 13K). */
	private const ORDER_VAR = 'affibloom_order';

	/** The query var that marks page()'s count, which keeps no ORDER BY (Task 37B). */
	private const COUNT_VAR = 'affibloom_count_only';

	/**
	 * Stores the post with every field in one call, as Lasso's meta_input does: a new product is inserted, one with an
	 * ID is updated in place (Task 15A), which also makes it the most recently changed on the Dashboard (F19's order).
	 * A value read and written back unchanged keeps its bytes: get_post_meta() gives it unslashed and wp_slash() is
	 * undone by WordPress on the way in.
	 *
	 * @param Product $product A new product, or a stored one with its changes.
	 * @throws RuntimeException When WordPress refuses the post.
	 */
	public function save( Product $product ): Product {
		$post    = array(
			'post_type'   => ProductPostType::NAME,
			'post_status' => 'publish',
			'post_title'  => $product->name,
			'meta_input'  => array(
				'_affibloom_url'                  => $product->url->value(),
				'_affibloom_url_key'              => $product->url->key(),
				// The base domain Link Opportunities matches links by (F22 D5; Task O1).
				'_affibloom_domain'               => BaseDomain::of_product( $product->url->value() ),
				'_affibloom_type'                 => $product->type->value,
				'_affibloom_new_tab'              => self::flag( $product->new_tab ),
				'_affibloom_nofollow'             => self::flag( $product->nofollow ),
				'_affibloom_sponsored'            => self::flag( $product->sponsored ),
				'_affibloom_show_disclosure'      => self::flag( $product->show_disclosure ),
				'_affibloom_cloak'                => self::flag( $product->cloak ),
				'_affibloom_detect_opportunities' => self::flag( $product->detect_opportunities ),
				'_affibloom_old_slugs'            => $product->old_slugs,
				'_affibloom_button_text'          => $product->button_text,
				'_affibloom_primary_position'     => (string) $product->buttons->main_position(),
				// '' is the display type's theme, and the site's Show Price (hydrate() reads them back that way).
				'_affibloom_theme'                => null === $product->theme ? '' : $product->theme->name(),
				'_affibloom_image_url'            => $product->image_url,
				'_affibloom_image_id'             => (string) $product->image_id,
				'_affibloom_badge'                => $product->badge,
				'_affibloom_price'                => $product->price,
				'_affibloom_show_price'           => null === $product->show_price ? '' : self::flag( $product->show_price ),
				'_affibloom_description'          => $product->description,
				'_affibloom_disclosure'           => $product->disclosure,
				'_affibloom_prime'                => self::flag( $product->prime ),
				// A JSON list of bullets (C1Q4; the user asked for JSON).
				'_affibloom_features'             => (string) wp_json_encode( $product->features ),
				'_affibloom_show_features'        => self::flag( $product->show_features ),
				'_affibloom_old_genius_url'       => $product->old_genius_url,
				'_affibloom_old_genius_url2'      => $product->old_genius_url2,
			),
		);
		$buttons = $product->buttons->to_array();
		if ( array() !== $buttons ) {
			$post['meta_input']['_affibloom_buttons'] = $buttons;
		}
		// As Lasso stores amazon_product_id and its country only for an Amazon product (save_lasso_url).
		if ( '' !== $product->asin ) {
			$post['meta_input']['_affibloom_asin']           = $product->asin;
			$post['meta_input']['_affibloom_amazon_country'] = $product->amazon_country;
		}
		// As Lasso writes url_without_arguments only for a link that is not Amazon's (Task 9C).
		$plain_key = PlainUrlKey::of( $product->url );
		if ( '' !== $plain_key ) {
			$post['meta_input']['_affibloom_url_plain_key'] = $plain_key;
		}
		// As Lasso fills url_details.search_text only for an Amazon search page.
		if ( '' !== $product->search_key ) {
			$post['meta_input']['_affibloom_search_key'] = $product->search_key;
		}

		// wp_insert_post() unslashes what it is given, so a link's backslashes survive only if slashed first.
		// wp_update_post() keeps what is not given (author, dates, slug) and expects the same slashing.
		if ( null === $product->id ) {
			// A new product's slug (F24 R3; Task CL4), made unique by WordPress; '' lets WordPress take the name's.
			$id = wp_insert_post( wp_slash( array( 'post_name' => $product->slug ) + $post ), true );
		} else {
			// The slug as stored, or '' for the name's (F24 R4, D12); WordPress makes it unique among products (R3).
			$id = wp_update_post(
				wp_slash(
					array(
						'ID'        => $product->id,
						'post_name' => $product->slug,
					) + $post
				),
				true
			);
		}
		if ( is_wp_error( $id ) ) {
			throw new RuntimeException( esc_html( $id->get_error_message() ) );
		}
		// The last button removed: no list is stored, as a new product has none.
		if ( array() === $buttons ) {
			delete_post_meta( $id, '_affibloom_buttons' );
		}
		$this->index_button_urls( $id, $product->buttons );
		// A product that is no longer Amazon's, or no longer a search page, loses those keys (Lasso:
		// delete_post_meta( 'amazon_product_id' ) when the new link is not Amazon's; Task 15B).
		if ( '' === $product->asin ) {
			delete_post_meta( $id, '_affibloom_asin' );
			delete_post_meta( $id, '_affibloom_amazon_country' );
		}
		if ( '' === $product->search_key ) {
			delete_post_meta( $id, '_affibloom_search_key' );
		}
		if ( '' === $plain_key ) {
			delete_post_meta( $id, '_affibloom_url_plain_key' );
		}

		return $product->with(
			array(
				'id'   => $id,
				'slug' => (string) get_post_field( 'post_name', $id ),
			)
		);
	}

	/**
	 * Writes `_affibloom_button_url` for every product that has added buttons, once: upgrade step 1 (Task 13G), for the
	 * products stored before save() wrote the key. Any status, so a draft or a retired product is found when it comes
	 * back.
	 *
	 * @return int How many products were indexed.
	 */
	public function index_all_button_urls(): int {
		$ids = get_posts(
			array(
				'post_type'        => ProductPostType::NAME,
				'post_status'      => 'any',
				'posts_per_page'   => -1,
				'fields'           => 'ids',
				'meta_key'         => '_affibloom_buttons', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- once, at an upgrade.
				'suppress_filters' => true,
			)
		);
		foreach ( $ids as $id ) {
			$id = (int) $id;
			$this->index_button_urls( $id, Buttons::from_array( (array) get_post_meta( $id, '_affibloom_buttons', true ) ) );
		}

		return count( $ids );
	}

	/**
	 * One `_affibloom_button_url` row per added button's link (Task 13G; F19 D4), rewritten whole: the Dashboard's
	 * search finds a product by these links only, as Lasso's finds it by `second_btn_url`, never by a button's text or
	 * the keys of the stored list.
	 *
	 * @param int     $id      Post ID.
	 * @param Buttons $buttons The product's added buttons.
	 */
	private function index_button_urls( int $id, Buttons $buttons ): void {
		delete_post_meta( $id, '_affibloom_button_url' );
		foreach ( $buttons->urls() as $url ) {
			add_post_meta( $id, '_affibloom_button_url', wp_slash( $url ) );
		}
	}

	/**
	 * Loads these products' posts and meta with two queries, into WordPress's caches, so get() reads them without one
	 * query per product (Task 37B).
	 *
	 * @param list<int> $ids Post IDs.
	 */
	public function prime( array $ids ): void {
		if ( array() !== $ids ) {
			_prime_post_caches( $ids, false, true );
		}
	}

	/**
	 * The published product with this ID, or null. Lasso fills its page only for a published record of its own type
	 * (get_lasso_url: LASSO_POST_TYPE === $post_type && 'publish' === $post_status).
	 *
	 * @param int $id Post ID.
	 */
	public function get( int $id ): ?Product {
		$post = get_post( $id );
		if ( ! $post instanceof WP_Post || ProductPostType::NAME !== $post->post_type || 'publish' !== $post->post_status ) {
			return null;
		}

		return $this->hydrate( $post );
	}

	/**
	 * A published product's custom fields (`_affibloom_fields`), or null.
	 *
	 * @param int $id Post ID.
	 */
	public function fields( int $id ): ?ProductFields {
		$post = get_post( $id );
		if ( ! $post instanceof WP_Post || ProductPostType::NAME !== $post->post_type || 'publish' !== $post->post_status ) {
			return null;
		}

		return ProductFields::from_array( (array) get_post_meta( $id, '_affibloom_fields', true ) );
	}

	/**
	 * Stores a product's custom fields; the post itself is not touched.
	 *
	 * @param int           $id     Post ID.
	 * @param ProductFields $fields The fields.
	 */
	public function save_fields( int $id, ProductFields $fields ): void {
		update_post_meta( $id, '_affibloom_fields', $fields->to_array() );
	}

	/**
	 * The published product whose link without its query string has this key, or null (Task 9C).
	 *
	 * @param string $key From PlainUrlKey::of().
	 */
	public function find_by_plain_key( string $key ): ?Product {
		return $this->find_one(
			array(
				array(
					'key'   => '_affibloom_url_plain_key',
					'value' => $key,
				),
			)
		);
	}

	/**
	 * Sets one link switch on every product of any status, in two statements, as Lasso's override does (one UPDATE, one
	 * INSERT for the products that never had the value). Nothing else on a product changes, and neither does its
	 * modified date, so the Dashboard's order stays.
	 *
	 * @param string $field The product field.
	 * @param bool   $value The new value.
	 * @return int How many products now hold the value.
	 * @throws InvalidArgumentException For a field that is not a link switch.
	 */
	public function set_flag_for_all( string $field, bool $value ): int {
		if ( ! in_array( $field, self::LINK_SWITCHES, true ) ) {
			throw new InvalidArgumentException( 'Not a link switch.' );
		}

		global $wpdb;
		$key  = '_affibloom_' . $field;
		$flag = self::flag( $value );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- one statement for all products is the point (edge case 5); the caches are flushed below.
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta} AS m INNER JOIN {$wpdb->posts} AS p ON p.ID = m.post_id SET m.meta_value = %s WHERE m.meta_key = %s AND p.post_type = %s",
				$flag,
				$key,
				ProductPostType::NAME
			)
		);
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$wpdb->postmeta} ( post_id, meta_key, meta_value ) SELECT p.ID, %s, %s FROM {$wpdb->posts} AS p WHERE p.post_type = %s AND NOT EXISTS ( SELECT 1 FROM {$wpdb->postmeta} AS m WHERE m.post_id = p.ID AND m.meta_key = %s )",
				$key,
				$flag,
				ProductPostType::NAME,
				$key
			)
		);
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} AS m INNER JOIN {$wpdb->posts} AS p ON p.ID = m.post_id WHERE m.meta_key = %s AND m.meta_value = %s AND p.post_type = %s",
				$key,
				$flag,
				ProductPostType::NAME
			)
		);
		// phpcs:enable

		wp_cache_flush_group( 'post_meta' );

		return $count;
	}

	/**
	 * The published product whose link has this key, or null.
	 *
	 * @param string $key From AffiliateUrl::key().
	 */
	public function find_by_url_key( string $key ): ?Product {
		return $this->find_one(
			array(
				array(
					'key'   => '_affibloom_url_key',
					'value' => $key,
				),
			)
		);
	}

	/**
	 * The published Amazon product with this ASIN on this store, or null. Lasso's query joins url_details on
	 * product_id, country and product_type "amazon", published posts only; meta_value's collation compares the ASIN
	 * without case, as Lasso's column does.
	 *
	 * @param string $asin    The ASIN.
	 * @param string $country The store's country code.
	 */
	public function find_amazon( string $asin, string $country ): ?Product {
		return $this->find_one(
			array(
				array(
					'key'   => '_affibloom_type',
					'value' => ProductType::Amazon->value,
				),
				array(
					'key'   => '_affibloom_asin',
					'value' => $asin,
				),
				array(
					'key'   => '_affibloom_amazon_country',
					'value' => $country,
				),
			)
		);
	}

	/**
	 * Published products whose Primary URL holds "geni.us", highest ID first, below an ID (C1P; Lasso's
	 * get_genius_links: LIKE '%geni.us%', newest first).
	 *
	 * @param int $before Only IDs below this one; 0 for no limit.
	 * @param int $limit  At most this many.
	 * @return list<int>
	 */
	public function genius_ids( int $before, int $limit ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- a job's batch, read once; WP_Query has no "ID below".
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT p.ID FROM {$wpdb->posts} AS p INNER JOIN {$wpdb->postmeta} AS m ON m.post_id = p.ID AND m.meta_key = %s WHERE p.post_type = %s AND p.post_status = 'publish' AND m.meta_value LIKE %s AND ( %d = 0 OR p.ID < %d ) ORDER BY p.ID DESC LIMIT %d",
				'_affibloom_url',
				ProductPostType::NAME,
				'%' . $wpdb->esc_like( 'geni.us' ) . '%',
				$before,
				$before,
				max( 1, $limit )
			)
		);

		return array_map( 'intval', $ids );
	}

	/**
	 * The published product whose Amazon search has this key, or null; meta_value's collation compares it without case.
	 *
	 * @param string $key From AmazonUrl::search_key().
	 */
	public function find_by_search_key( string $key ): ?Product {
		return $this->find_one(
			array(
				array(
					'key'   => '_affibloom_search_key',
					'value' => $key,
				),
			)
		);
	}

	/**
	 * A deleted product a link belongs to (Task 17D): the trash (the Dashboard's delete) or retired (the repoint), by the
	 * published lookups' arms in their order.
	 *
	 * @param list<string> $url_keys   Keys from AffiliateUrl::key().
	 * @param string       $asin       An Amazon link's ASIN, or ''.
	 * @param string       $country    Its store's country code.
	 * @param string       $search_key An Amazon search page's key, or ''.
	 */
	public function find_deleted( array $url_keys, string $asin, string $country, string $search_key ): ?Product {
		$deleted = array( 'trash', ProductPostType::RETIRED );
		$found   = null;
		if ( array() !== $url_keys ) {
			$found = $this->find_one(
				array(
					array(
						'key'     => '_affibloom_url_key',
						'value'   => array_values( array_unique( $url_keys ) ),
						'compare' => 'IN',
					),
				),
				$deleted
			);
		}
		if ( null === $found && '' !== $asin ) {
			$found = $this->find_one(
				array(
					array(
						'key'   => '_affibloom_type',
						'value' => ProductType::Amazon->value,
					),
					array(
						'key'   => '_affibloom_asin',
						'value' => $asin,
					),
					array(
						'key'   => '_affibloom_amazon_country',
						'value' => $country,
					),
				),
				$deleted
			);
		}
		if ( null === $found && '' !== $search_key ) {
			$found = $this->find_one(
				array(
					array(
						'key'   => '_affibloom_search_key',
						'value' => $search_key,
					),
				),
				$deleted
			);
		}

		return $found;
	}

	/**
	 * Publishes a deleted product again (Task 17D), as Lasso's add flow (wp_update_post with the publish status). The
	 * trash gave its slug a __trashed suffix and kept the old one in _wp_desired_post_slug, as wp_untrash_post() reads it;
	 * the slug is put back the same way. A retired product's pointer goes (F1 D42).
	 *
	 * @param Product $product A trashed or retired product.
	 * @throws RuntimeException When WordPress refuses it.
	 */
	public function revive( Product $product ): Product {
		$id      = (int) $product->id;
		$changes = array(
			'ID'          => $id,
			'post_status' => 'publish',
		);
		$slug    = (string) get_post_meta( $id, '_wp_desired_post_slug', true );
		if ( '' !== $slug ) {
			$changes['post_name'] = $slug;
		}
		$result = wp_update_post( wp_slash( $changes ), true );
		if ( is_wp_error( $result ) ) {
			throw new RuntimeException( esc_html( $result->get_error_message() ) );
		}
		delete_post_meta( $id, '_wp_desired_post_slug' );
		delete_post_meta( $id, '_affibloom_repointed_to' );

		return $product;
	}

	/**
	 * Sets a stored product's image link alone, as Lasso's Refresh writes lasso_custom_thumbnail (update_post_meta): the
	 * media library item is cleared, and the post is not touched, so the product keeps its place in "most recently
	 * changed".
	 *
	 * @param Product $product A stored product.
	 * @param string  $url     The image link.
	 */
	public function set_image( Product $product, string $url ): Product {
		update_post_meta( (int) $product->id, '_affibloom_image_url', wp_slash( $url ) );
		update_post_meta( (int) $product->id, '_affibloom_image_id', '0' );

		return $product->with(
			array(
				'image_url' => $url,
				'image_id'  => 0,
			)
		);
	}

	/**
	 * Sets a stored product's feature bullets alone (post meta only), so it keeps its place in "most recently changed".
	 *
	 * @param Product      $product  A stored product.
	 * @param list<string> $features The bullets.
	 */
	public function set_features( Product $product, array $features ): Product {
		update_post_meta( (int) $product->id, '_affibloom_features', wp_slash( (string) wp_json_encode( $features ) ) );

		return $product->with( array( 'features' => $features ) );
	}

	/**
	 * The feature bullets as stored (a JSON list); anything else reads as none.
	 *
	 * @param string $stored The meta value.
	 * @return list<string>
	 */
	private static function features( string $stored ): array {
		$list = json_decode( $stored, true );

		return is_array( $list ) ? array_values( array_filter( $list, 'is_string' ) ) : array();
	}

	/**
	 * Gives a stored product a new name, as Lasso's re-title does (wp_update_post with the title).
	 *
	 * @param Product $product A stored product.
	 * @param string  $name    Its new name.
	 * @throws RuntimeException When WordPress refuses the change.
	 */
	public function rename( Product $product, string $name ): Product {
		$result = wp_update_post(
			wp_slash(
				array(
					'ID'         => (int) $product->id,
					'post_title' => $name,
				)
			),
			true
		);
		if ( is_wp_error( $result ) ) {
			throw new RuntimeException( esc_html( $result->get_error_message() ) );
		}

		return $product->with( array( 'name' => $name ) );
	}

	/**
	 * The repoint (Task 15C): the pointer first, as Lasso writes lasso_repointed_to_lasso_id before it soft-deletes, so a
	 * product is never retired without it; then the retired status (Task 17C), Lasso's lasso_delete. Not
	 * wp_trash_post(): it marks the post for WordPress's 30-day emptying, which would delete the product and its pointer.
	 *
	 * @param Product $product A stored product.
	 * @param int     $to      The product it points to.
	 * @throws RuntimeException When WordPress refuses either step.
	 */
	public function retire( Product $product, int $to ): void {
		$id      = (int) $product->id;
		$pointer = (string) $to;
		update_post_meta( $id, '_affibloom_repointed_to', $pointer );
		$written = (string) get_post_meta( $id, '_affibloom_repointed_to', true );
		if ( $written !== $pointer ) {
			throw new RuntimeException( 'Could not save the repoint.' );
		}
		$retired = wp_update_post(
			array(
				'ID'          => $id,
				'post_status' => ProductPostType::RETIRED,
			),
			true
		);
		if ( is_wp_error( $retired ) ) {
			throw new RuntimeException( 'Could not retire the product.' );
		}
	}

	/**
	 * The published or retired product with this slug, or null (F24 D3: a cloaked address answers for both).
	 *
	 * @param string $slug A slug.
	 */
	public function find_by_slug( string $slug ): ?Product {
		$slug = sanitize_title( $slug );
		if ( '' === $slug ) {
			return null;
		}
		$posts = get_posts(
			array(
				'post_type'        => ProductPostType::NAME,
				'name'             => $slug,
				'post_status'      => array( 'publish', ProductPostType::RETIRED ),
				'posts_per_page'   => 1,
				'no_found_rows'    => true,
				'suppress_filters' => true,
			)
		);

		return isset( $posts[0] ) ? $this->hydrate( $posts[0] ) : null;
	}

	/**
	 * The published or retired product that had this slug before, or null (Task CL10). The old slugs are one stored
	 * list, so the slug is matched with its quotes, as it stands in the serialised list.
	 *
	 * @param string $slug A slug.
	 */
	public function find_by_old_slug( string $slug ): ?Product {
		$slug = sanitize_title( $slug );
		if ( '' === $slug ) {
			return null;
		}
		$posts = get_posts(
			array(
				'post_type'        => ProductPostType::NAME,
				'post_status'      => array( 'publish', ProductPostType::RETIRED ),
				'posts_per_page'   => 1,
				'orderby'          => 'modified',
				'no_found_rows'    => true,
				'suppress_filters' => true,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- only for an address no post or product answers.
				'meta_query'       => array(
					array(
						'key'     => '_affibloom_old_slugs',
						'value'   => '"' . $slug . '"',
						'compare' => 'LIKE',
					),
				),
			)
		);

		return isset( $posts[0] ) ? $this->hydrate( $posts[0] ) : null;
	}

	/**
	 * The ID of the product with this slug, whatever its status, or null (F31 A2).
	 *
	 * @param string $slug A product's slug.
	 */
	public function id_by_slug( string $slug ): ?int {
		$slug = sanitize_title( $slug );
		if ( '' === $slug ) {
			return null;
		}
		$ids = get_posts(
			array(
				'post_type'        => ProductPostType::NAME,
				'name'             => $slug,
				'post_status'      => 'any',
				'posts_per_page'   => 1,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => true,
			)
		);

		return array() === $ids ? null : (int) $ids[0];
	}

	/**
	 * The product a product was repointed to, whatever its status (Task 18A).
	 *
	 * @param int $id A product's ID.
	 * @return int|null The target's ID, 0 when it points nowhere, null when no product with this ID exists.
	 */
	public function repointed_to( int $id ): ?int {
		$post = get_post( $id );
		if ( ! $post instanceof WP_Post || ProductPostType::NAME !== $post->post_type ) {
			return null;
		}

		return max( 0, (int) get_post_meta( $id, '_affibloom_repointed_to', true ) );
	}

	/**
	 * The product page's delete (Task 17A), as Lasso's lasso_delete_post: wp_delete_post( id, true ) skips the trash and
	 * removes the post with its meta and term relationships. WordPress answers false or null when it refuses.
	 *
	 * @param int $id A stored product's ID.
	 * @return bool False when WordPress refused the delete.
	 */
	public function delete( int $id ): bool {
		return (bool) wp_delete_post( $id, true );
	}

	/**
	 * The Dashboard's delete (Task 17B), as Lasso's lasso_bulk_remove_links: the status becomes trash through
	 * wp_update_post(), not wp_trash_post(). wp_trash_post() writes _wp_trash_meta_time, which WordPress's daily
	 * wp_scheduled_delete() reads to delete posts trashed 30 days ago; Lasso's way sets no such mark, so a trashed
	 * product stays until its link is added again (F1 X11, D40). wp_insert_post() still gives the slug its __trashed
	 * suffix, so a new product may take the name.
	 *
	 * @param int $id A stored product's ID.
	 * @return bool False when WordPress refused it.
	 */
	public function trash( int $id ): bool {
		$result = wp_update_post(
			array(
				'ID'          => $id,
				'post_status' => 'trash',
			),
			true
		);

		// With $wp_error true, WordPress reports every refusal (no such post included) as a WP_Error, never as 0.
		return ! is_wp_error( $result );
	}

	/**
	 * One page of the published products that match the search, most recently changed first (Lasso's Dashboard
	 * order).
	 *
	 * The total comes from its own query, as Lasso's does (Lasso_DB::get_dashboard_link_count), not from
	 * WP_Query's found_posts: on MySQL 8.4 a page past the end makes SQL_CALC_FOUND_ROWS report 0, which would
	 * turn page 99 of a full library into an empty one. Knowing the total first also lets a page past the end
	 * show the last page, as Lasso's range does, instead of nothing.
	 *
	 * WP_Query primes the meta cache for the page, so building each product reads no further rows; the term
	 * cache is left alone because no column on this screen shows a taxonomy.
	 *
	 * @param SearchTerm        $term      What to look for; an empty term lists everything.
	 * @param LinkKind          $link_kind Lasso's "Link type" filter: Amazon's products, the others', or all (Task 13E).
	 * @param int               $page      Page number, from 1.
	 * @param int               $per_page  Products per page; 0 counts only (the total, no items: Task 37B).
	 * @param int               $group     The Group filter (F23 R22): 0 for all, -1 for products in no group, else a group's ID.
	 * @param int               $post_id   One product only (Lasso's `?post_id=`, F19 R30): 0 for all; still published only.
	 * @param AlertListing|null $alerts The Alert sort and the alert tab (F11, Tasks 31A, 31B); null for neither.
	 * @param Placement         $placement The Placements filter (F12 L13, Task 36D).
	 * @param ListOrder         $order     The header sort (F19 R1-R6, D8, D9; Task 13K).
	 * @param TableFilter       $tables    The Comparison table filter (F25 R59, Task T16).
	 */
	public function page( SearchTerm $term, LinkKind $link_kind, int $page, int $per_page, int $group = 0, int $post_id = 0, ?AlertListing $alerts = null, Placement $placement = Placement::Any, ListOrder $order = new ListOrder(), TableFilter $tables = TableFilter::Any ): ProductPage {
		$count_only = $per_page < 1;
		$per_page   = max( 1, $per_page );
		$list       = array(
			'post_type'              => ProductPostType::NAME,
			'post_status'            => 'publish',
			// Lasso's order (post_modified DESC) has no second key, so products sharing a time could show on two pages
			// or none; the highest ID decides a tie (Task 13I; F19 D9). No order Lasso defines changes.
			'orderby'                => array(
				'modified' => 'DESC',
				'ID'       => 'DESC',
			),
			'ignore_sticky_posts'    => true,
			'update_post_term_cache' => false,
		);

		if ( ! $term->is_empty() ) {
			$list[ self::SEARCH_VAR ] = $term;
		}

		// Lasso: url_details.product_type = amazon, or not (a product with no type counts as not Amazon's).
		if ( LinkKind::Amazon === $link_kind ) {
			$list['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- one indexed key, a few hundred posts.
				array(
					'key'   => '_affibloom_type',
					'value' => ProductType::Amazon->value,
				),
			);
		} elseif ( LinkKind::NonAmazon === $link_kind ) {
			$list['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- as above.
				'relation' => 'OR',
				array(
					'key'     => '_affibloom_type',
					'value'   => ProductType::Amazon->value,
					'compare' => '!=',
				),
				array(
					'key'     => '_affibloom_type',
					'compare' => 'NOT EXISTS',
				),
			);
		}

		// Lasso's `?post_id=` (class-ajax.php 126-132: p.ID = %d), with the other filters still applied.
		if ( $post_id > 0 ) {
			$list['p'] = $post_id;
		}

		// Lasso's Group filter (dashboard.php 158-165): one group's products, or the products in no group.
		if ( -1 === $group ) {
			$list['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- a handful of groups, a few hundred posts.
				array(
					'taxonomy' => GroupTaxonomy::NAME,
					'operator' => 'NOT EXISTS',
				),
			);
		} elseif ( $group > 0 ) {
			$list['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- as above.
				array(
					'taxonomy'         => GroupTaxonomy::NAME,
					'field'            => 'term_id',
					'terms'            => array( $group ),
					'include_children' => false,
				),
			);
		}

		// Lasso's Placements filter (dashboard.php 174-181; F12 L13, Task 36D): used somewhere by the Locations count's rule, or not.
		if ( Placement::Any !== $placement ) {
			$list[ self::PLACEMENT_VAR ] = $placement;
		}
		// Lasso's Comparison table filter (dashboard.php 182-189; F25 R59, Task T16): in a table, or in none.
		if ( TableFilter::Any !== $tables ) {
			$in_tables = ( new PostTypeTableRepository() )->product_ids();
			if ( TableFilter::InTable === $tables ) {
				$list['post__in'] = array() === $in_tables ? array( 0 ) : $in_tables;
			} elseif ( array() !== $in_tables ) {
				$list['post__not_in'] = $in_tables; // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- the products of a handful of tables.
			}
		}
		// The Locations and Clicks headers' sort (F19 R6, D9; Task 13K), and the Opportunities tab's count (F22 R52, Task
		// O8); Alert's is the alert listing's, below.
		if ( in_array( $order->column, array( ListOrder::LOCATIONS, ListOrder::CLICKS, ListOrder::OPPORTUNITY_COUNT ), true ) ) {
			$list[ self::ORDER_VAR ] = $order;
		}

		$filter  = $this->search_filter();
		$clauses = null !== $alerts && $alerts->is_active() ? self::alert_clauses( $alerts ) : null;
		add_filter( 'posts_where', $filter, 10, 2 );
		add_filter( 'posts_where', array( self::class, 'placement_where' ), 10, 2 );
		add_filter( 'posts_orderby', array( self::class, 'column_orderby' ), 10, 2 );
		if ( null !== $clauses ) {
			$list[ self::ALERTS_VAR ] = true;
			add_filter( 'posts_clauses', $clauses, 10, 2 );
		}

		try {
			// The count needs no order: without one, the Locations, Clicks and Alert sorts are worked out once, for the
			// page, not twice (Task 37B, finding P2).
			$counter = new WP_Query(
				array(
					'fields'         => 'ids',
					'posts_per_page' => 1,
					'orderby'        => 'none',
					self::COUNT_VAR  => true,
				) + $list
			);
			$total   = (int) $counter->found_posts;
			$pages   = max( 1, (int) ceil( $total / $per_page ) );
			$wanted  = min( max( 1, $page ), $pages );

			$items = array();
			if ( $total > 0 && ! $count_only ) {
				$query = new WP_Query(
					$list + array(
						'posts_per_page' => $per_page,
						'paged'          => $wanted,
						'no_found_rows'  => true,
					)
				);
				foreach ( $query->posts as $post ) {
					if ( $post instanceof WP_Post ) {
						$items[] = $this->hydrate( $post );
					}
				}
			}
		} finally {
			remove_filter( 'posts_where', $filter, 10 );
			remove_filter( 'posts_where', array( self::class, 'placement_where' ), 10 );
			remove_filter( 'posts_orderby', array( self::class, 'column_orderby' ), 10 );
			if ( null !== $clauses ) {
				remove_filter( 'posts_clauses', $clauses, 10 );
			}
		}

		return new ProductPage( $items, $total, $wanted, $per_page );
	}

	/**
	 * The Locations or Clicks sort as the two queries' ORDER BY, and for them only (F19 R6, D9; Task 13K): the product's
	 * Locations count (the column's rule, TableLinkLocationRepository::count_sql()), its clicks in the last 30 days
	 * (TableClickStore::total_sql()) or the posts with an opportunity for it (TableOpportunityRepository::count_sql(),
	 * F22 R52, Task O8), then the highest ID on a tie.
	 *
	 * @param string   $orderby The query's ORDER BY.
	 * @param WP_Query $query   The query.
	 */
	public static function column_orderby( string $orderby, WP_Query $query ): string {
		global $wpdb;

		$order = $query->get( self::ORDER_VAR );
		if ( ! $order instanceof ListOrder || true === $query->get( self::COUNT_VAR ) ) {
			return $orderby;
		}
		$direction = ListOrder::ASC === $order->direction ? 'ASC' : 'DESC';
		$value     = match ( $order->column ) {
			ListOrder::LOCATIONS         => TableLinkLocationRepository::count_sql( "{$wpdb->posts}.ID" ),
			ListOrder::OPPORTUNITY_COUNT => TableOpportunityRepository::count_sql( "{$wpdb->posts}.ID" ),
			default                      => TableClickStore::total_sql( "{$wpdb->posts}.ID", ClickReport::since( time() ) ),
		};

		return "{$value} {$direction}, {$wpdb->posts}.ID DESC";
	}

	/**
	 * The Placements filter as a WHERE for the two queries above, and for them only (F12 L13; Task 36D): the product is
	 * used, or not, by TableLinkLocationRepository's rule.
	 *
	 * @param string   $where The query's WHERE.
	 * @param WP_Query $query The query.
	 */
	public static function placement_where( string $where, WP_Query $query ): string {
		global $wpdb;

		$placement = $query->get( self::PLACEMENT_VAR );
		if ( ! $placement instanceof Placement || Placement::Any === $placement ) {
			return $where;
		}
		$used = TableLinkLocationRepository::used_sql( "{$wpdb->posts}.ID" );

		return $where . ( Placement::InContent === $placement ? " AND {$used}" : " AND NOT {$used}" );
	}

	/**
	 * The Alert sort and the alert tab as SQL for the two queries above (F11 R20, R24-R26; Tasks 31A, 31B): the
	 * product's link-health row joined by its ID (one row per product, so totals do not change), the shown issue's
	 * date (an open broken issue's, or an open out-of-stock one's once its grace days have passed) as the sort key,
	 * products without one last, then the list's own order. Only queries that carry ALERTS_VAR are changed.
	 *
	 * @param AlertListing $alerts The sort and the tab.
	 * @return callable(array<string, string>, WP_Query): array<string, string>
	 */
	private static function alert_clauses( AlertListing $alerts ): callable {
		return static function ( array $clauses, WP_Query $query ) use ( $alerts ): array {
			global $wpdb;

			if ( true !== $query->get( self::ALERTS_VAR ) ) {
				return $clauses;
			}
			$issues           = $wpdb->prefix . TableLinkIssueRepository::TABLE;
			$cutoff           = $alerts->cutoff->setTimezone( new \DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
			$broken           = $wpdb->prepare( 'afi.resolved = 0 AND afi.issue_type NOT IN (%s, %s)', IssueType::NONE, IssueType::OUT_OF_STOCK );
			$out              = $wpdb->prepare( 'afi.resolved = 0 AND afi.issue_type = %s AND afi.detection_date <= %s', IssueType::OUT_OF_STOCK, $cutoff );
			$shown            = "CASE WHEN {$broken} THEN afi.detection_date WHEN {$out} THEN afi.detection_date END";
			$clauses['join'] .= $wpdb->prepare( " LEFT JOIN %i afi ON afi.product_id = {$wpdb->posts}.ID", $issues );
			if ( AlertListing::BROKEN === $alerts->tab ) {
				$clauses['where'] .= " AND {$broken}";
			} elseif ( AlertListing::OUT_OF_STOCK === $alerts->tab ) {
				$clauses['where'] .= " AND {$out}";
			} elseif ( AlertListing::OPPORTUNITIES === $alerts->tab ) {
				// F22 R50, R52, D13 (Task O8): the products with a Link Opportunity, by D4's rule.
				$with              = TableOpportunityRepository::product_ids();
				$clauses['where'] .= array() === $with ? ' AND 1 = 0' : " AND {$wpdb->posts}.ID IN (" . implode( ',', $with ) . ')';
			}
			if ( '' !== $alerts->order && true !== $query->get( self::COUNT_VAR ) ) {
				$direction          = AlertListing::DESC === $alerts->order ? 'DESC' : 'ASC';
				$clauses['orderby'] = "({$shown}) IS NULL ASC, ({$shown}) {$direction}, {$wpdb->posts}.post_modified DESC, {$wpdb->posts}.ID DESC";
			}

			return $clauses;
		};
	}

	/**
	 * How many published products the library holds. WordPress counts a post type's statuses once and caches
	 * them, so the search box's placeholder and the Products tile cost no query of their own.
	 */
	public function count(): int {
		$counts = wp_count_posts( ProductPostType::NAME );

		return isset( $counts->publish ) ? (int) $counts->publish : 0;
	}

	/**
	 * Lasso's Dashboard search, as a WHERE clause for the two queries above and for them only: the filter does
	 * nothing unless the query carries the term (Lasso_DB::build_dashboard_search_term_string). It is a WHERE
	 * and not WordPress's own search, which matches the title and the content word by word and would find a
	 * product by neither its link nor its ASIN; the query is also not marked as a search, so nothing else on
	 * the site treats this admin list as one.
	 *
	 * The buttons after the primary one (F29) are searched by their links only, as Lasso's search matches the second
	 * button's link (60 of the 263 live records have one): one `_affibloom_button_url` row each (Task 13G), not the
	 * serialised list, whose button texts and keys (`url`, `nofollow`) would match too.
	 * A product is found by its URL variants too (F27 R20; Task V5): a variant's name or key containing the term, and an
	 * all-digit term a variant's number, as Lasso_DB's search joins lasso_url_variants.
	 *
	 * @return callable(string, \WP_Query): string
	 */
	private function search_filter(): callable {
		global $wpdb;

		return static function ( string $where, WP_Query $query ) use ( $wpdb ): string {
			$term = $query->get( self::SEARCH_VAR );
			if ( ! $term instanceof SearchTerm || $term->is_empty() ) {
				return $where;
			}

			$like = $term->like( static fn( string $value ): string => $wpdb->esc_like( $value ) );
			$arms = array(
				$wpdb->prepare( "{$wpdb->posts}.post_title LIKE %s", $like ),
				$wpdb->prepare(
					"EXISTS ( SELECT 1 FROM {$wpdb->postmeta} AS affibloom_m
						WHERE affibloom_m.post_id = {$wpdb->posts}.ID
							AND affibloom_m.meta_key IN ( '_affibloom_url', '_affibloom_asin', '_affibloom_button_url' )
							AND affibloom_m.meta_value LIKE %s )",
					$like
				),
			);

			$variants = $wpdb->prefix . TableVariantRepository::TABLE;
			$arms[]   = $wpdb->prepare(
				"EXISTS ( SELECT 1 FROM %i AS affibloom_v
					WHERE affibloom_v.parent_id = {$wpdb->posts}.ID
						AND ( affibloom_v.name LIKE %s OR affibloom_v.variant_key LIKE %s ) )",
				$variants,
				$like,
				$like
			);

			$id = $term->post_id();
			if ( null !== $id ) {
				$arms[] = $wpdb->prepare( "{$wpdb->posts}.ID = %d", $id );
				$arms[] = $wpdb->prepare( "EXISTS ( SELECT 1 FROM %i AS affibloom_vid WHERE affibloom_vid.parent_id = {$wpdb->posts}.ID AND affibloom_vid.id = %d )", $variants, $id );
			}

			$asin = $term->asin();
			if ( null !== $asin ) {
				$arms[] = $wpdb->prepare(
					"EXISTS ( SELECT 1 FROM {$wpdb->postmeta} AS affibloom_asin
						WHERE affibloom_asin.post_id = {$wpdb->posts}.ID
							AND affibloom_asin.meta_key = '_affibloom_asin'
							AND affibloom_asin.meta_value = %s )",
					$asin
				);
			}

			return $where . ' AND ( ' . implode( ' OR ', $arms ) . ' ) ';
		};
	}

	/**
	 * The product whose meta matches every clause, or null. Extracted on its second use (Task 12B). One lookup per add
	 * over a few hundred products, so the meta query's cost does not matter. Published products: the lowest ID first;
	 * trashed and retired ones (Task 17D): the newest first, as Lasso's inactive-record lookups order them.
	 *
	 * @param list<array{key: string, value: string|list<string>, compare?: string}> $clauses  Meta conditions, all required.
	 * @param list<string>                                                           $statuses Post statuses to look in.
	 */
	private function find_one( array $clauses, array $statuses = array( 'publish' ) ): ?Product {
		$posts = get_posts(
			array(
				'post_type'        => ProductPostType::NAME,
				'post_status'      => $statuses,
				'posts_per_page'   => 1,
				'orderby'          => 'ID',
				'order'            => array( 'publish' ) === $statuses ? 'ASC' : 'DESC',
				'no_found_rows'    => true,
				'suppress_filters' => true,
				'meta_query'       => array_merge( array( 'relation' => 'AND' ), $clauses ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- see above.
			)
		);

		return isset( $posts[0] ) ? $this->hydrate( $posts[0] ) : null;
	}

	/**
	 * The stored old slugs as a list of strings (F24 N1).
	 *
	 * @param mixed $stored The meta's value.
	 * @return list<string>
	 */
	private static function old_slugs( mixed $stored ): array {
		return is_array( $stored ) ? array_values( array_filter( array_map( 'strval', $stored ), static fn( string $slug ): bool => '' !== $slug ) ) : array();
	}

	/**
	 * A product from its post and meta.
	 *
	 * @param WP_Post $post A product post.
	 */
	private function hydrate( WP_Post $post ): Product {
		$meta = static fn( string $key ): string => (string) get_post_meta( $post->ID, '_affibloom_' . $key, true );

		// A flag is on only when stored as "1", as Lasso reads its own (1 === intval( get_post_meta( … ) )).
		return new Product(
			id: $post->ID,
			name: $post->post_title,
			url: AffiliateUrl::stored( $meta( 'url' ) ),
			type: ProductType::tryFrom( $meta( 'type' ) ) ?? ProductType::Other,
			new_tab: '1' === $meta( 'new_tab' ),
			nofollow: '1' === $meta( 'nofollow' ),
			sponsored: '1' === $meta( 'sponsored' ),
			show_disclosure: '1' === $meta( 'show_disclosure' ),
			asin: $meta( 'asin' ),
			amazon_country: $meta( 'amazon_country' ),
			search_key: $meta( 'search_key' ),
			image_url: $meta( 'image_url' ),
			button_text: $meta( 'button_text' ),
			buttons: Buttons::from_array( (array) get_post_meta( $post->ID, '_affibloom_buttons', true ), (int) $meta( 'primary_position' ) ),
			// '' means the display type's theme; an unknown stored name reads as Lasso's default theme.
			theme: '' === $meta( 'theme' ) ? null : Theme::from( $meta( 'theme' ) ),
			badge: $meta( 'badge' ),
			price: $meta( 'price' ),
			// '' means the site's Show Price, as Lasso reads show_price.
			show_price: '' === $meta( 'show_price' ) ? null : '1' === $meta( 'show_price' ),
			description: $meta( 'description' ),
			disclosure: $meta( 'disclosure' ),
			// C1G (decision C4): the Prime switch is an opt-out, on unless an editor stored it off.
			prime: '0' !== $meta( 'prime' ),
			image_id: (int) $meta( 'image_id' ),
			features: self::features( $meta( 'features' ) ),
			old_genius_url: $meta( 'old_genius_url' ),
			old_genius_url2: $meta( 'old_genius_url2' ),
			// '' (never stored) means on, and any other value counts as PHP's truth, as Lasso reads show_description.
			show_description: '' === $meta( 'show_description' ) ? null : '0' !== $meta( 'show_description' ),
			slug: $post->post_name,
			// Missing is off, as Lasso reads link_cloaking with intval (F24 D1).
			cloak: '1' === $meta( 'cloak' ),
			old_slugs: self::old_slugs( get_post_meta( $post->ID, '_affibloom_old_slugs', true ) ),
			show_features: '1' === $meta( 'show_features' ),
			// Missing is on: Lasso's is_opportunity defaults to 1, and Link Opportunities reads it so (F22 D4, D9).
			detect_opportunities: '0' !== $meta( 'detect_opportunities' ),
		);
	}

	/**
	 * A flag as stored: "1" or "0" (docs/spec/interface.md, section 4).
	 *
	 * @param bool $on The flag.
	 */
	private static function flag( bool $on ): string {
		return $on ? '1' : '0';
	}

	/**
	 * Whether a retired product has this ASIN on this store (Task AM1).
	 *
	 * @param string $asin    The ASIN.
	 * @param string $country The store's country code.
	 */
	public function is_retired_asin( string $asin, string $country ): bool {
		$found = get_posts(
			array(
				'post_type'      => ProductPostType::NAME,
				'post_status'    => ProductPostType::RETIRED,
				'fields'         => 'ids',
				'posts_per_page' => 1,
				'no_found_rows'  => true,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- two keys, one import link.
					array(
						'key'   => '_affibloom_asin',
						'value' => $asin,
					),
					array(
						'key'   => '_affibloom_amazon_country',
						'value' => $country,
					),
				),
			)
		);

		return array() !== $found;
	}
}
