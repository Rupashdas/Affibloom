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
use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Domain\Product\ProductType;
use RuntimeException;
use WP_Post;

/**
 * Products as affibloom_product posts with _affibloom_* meta (docs/spec/interface.md, section 4).
 */
final class PostTypeProductRepository implements ProductRepository {

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
