<?php
/**
 * Products as affibloom_product posts with _affibloom_* meta.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductPage;
use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Domain\Product\SearchTerm;
use RuntimeException;
use WP_Post;
use WP_Query;

/**
 * Products as affibloom_product posts with _affibloom_* meta (docs/spec/interface.md, section 4).
 */
final class PostTypeProductRepository implements ProductRepository {

	/** The query variable that carries the search to the WHERE filter, and nothing else. */
	private const SEARCH_VAR = 'affibloom_search';

	/**
	 * Inserts the post with its meta in one call, as Lasso's meta_input does. Updating arrives with Task 15.
	 *
	 * @param Product $product A new product.
	 * @throws RuntimeException When WordPress refuses the post.
	 */
	public function save( Product $product ): Product {
		$post = array(
			'post_type'   => ProductPostType::NAME,
			'post_status' => 'publish',
			'post_title'  => $product->name,
			'meta_input'  => array(
				'_affibloom_url'             => $product->url->value(),
				'_affibloom_url_key'         => $product->url->key(),
				'_affibloom_type'            => $product->type->value,
				'_affibloom_new_tab'         => self::flag( $product->new_tab ),
				'_affibloom_nofollow'        => self::flag( $product->nofollow ),
				'_affibloom_sponsored'       => self::flag( $product->sponsored ),
				'_affibloom_show_disclosure' => self::flag( $product->show_disclosure ),
			),
		);
		// As Lasso stores amazon_product_id and its country only for an Amazon product (save_lasso_url).
		if ( '' !== $product->asin ) {
			$post['meta_input']['_affibloom_asin']           = $product->asin;
			$post['meta_input']['_affibloom_amazon_country'] = $product->amazon_country;
		}
		// As Lasso fills url_details.search_text only for an Amazon search page.
		if ( '' !== $product->search_key ) {
			$post['meta_input']['_affibloom_search_key'] = $product->search_key;
		}

		// wp_insert_post() unslashes what it is given, so a link's backslashes survive only if slashed first.
		$id = wp_insert_post( wp_slash( $post ), true );
		if ( is_wp_error( $id ) ) {
			throw new RuntimeException( esc_html( $id->get_error_message() ) );
		}

		return $product->with( array( 'id' => $id ) );
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
	 * @param SearchTerm $term     What to look for; an empty term lists everything.
	 * @param int        $page     Page number, from 1.
	 * @param int        $per_page Products per page.
	 */
	public function page( SearchTerm $term, int $page, int $per_page ): ProductPage {
		$per_page = max( 1, $per_page );
		$list     = array(
			'post_type'              => ProductPostType::NAME,
			'post_status'            => 'publish',
			'orderby'                => 'modified',
			'order'                  => 'DESC',
			'ignore_sticky_posts'    => true,
			'update_post_term_cache' => false,
		);

		if ( ! $term->is_empty() ) {
			$list[ self::SEARCH_VAR ] = $term;
		}

		$filter = $this->search_filter();
		add_filter( 'posts_where', $filter, 10, 2 );

		try {
			$counter = new WP_Query(
				$list + array(
					'fields'         => 'ids',
					'posts_per_page' => 1,
				)
			);
			$total   = (int) $counter->found_posts;
			$pages   = max( 1, (int) ceil( $total / $per_page ) );
			$wanted  = min( max( 1, $page ), $pages );

			$items = array();
			if ( $total > 0 ) {
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
		}

		return new ProductPage( $items, $total, $wanted, $per_page );
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
	 * Task 15 adds `_affibloom_url2` to the meta keys when the second button's link exists: 60 of the 263 live
	 * Lasso records have one, and Lasso's search matches it. URL variants join if that subsystem is ever built.
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
							AND affibloom_m.meta_key IN ( '_affibloom_url', '_affibloom_asin' )
							AND affibloom_m.meta_value LIKE %s )",
					$like
				),
			);

			$id = $term->post_id();
			if ( null !== $id ) {
				$arms[] = $wpdb->prepare( "{$wpdb->posts}.ID = %d", $id );
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
	 * The published product whose meta matches every clause, lowest ID first, or null. Extracted on its second use
	 * (Task 12B). One lookup per add over a few hundred products, so the meta query's cost does not matter.
	 *
	 * @param list<array{key: string, value: string}> $clauses Meta key and value pairs, all required.
	 */
	private function find_one( array $clauses ): ?Product {
		$posts = get_posts(
			array(
				'post_type'        => ProductPostType::NAME,
				'post_status'      => 'publish',
				'posts_per_page'   => 1,
				'orderby'          => 'ID',
				'order'            => 'ASC',
				'no_found_rows'    => true,
				'suppress_filters' => true,
				'meta_query'       => array_merge( array( 'relation' => 'AND' ), $clauses ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- see above.
			)
		);

		return isset( $posts[0] ) ? $this->hydrate( $posts[0] ) : null;
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
			$post->ID,
			$post->post_title,
			AffiliateUrl::from_string( $meta( 'url' ) ),
			ProductType::tryFrom( $meta( 'type' ) ) ?? ProductType::Other,
			'1' === $meta( 'new_tab' ),
			'1' === $meta( 'nofollow' ),
			'1' === $meta( 'sponsored' ),
			'1' === $meta( 'show_disclosure' ),
			$meta( 'asin' ),
			$meta( 'amazon_country' ),
			$meta( 'search_key' ),
			$meta( 'image_url' ),
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
}
