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
				'_affibloom_url'     => $product->url->value(),
				'_affibloom_url_key' => $product->url->key(),
				'_affibloom_type'    => $product->type->value,
			),
		);

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
		$posts = get_posts(
			array(
				'post_type'        => ProductPostType::NAME,
				'post_status'      => 'publish',
				'posts_per_page'   => 1,
				'orderby'          => 'ID',
				'order'            => 'ASC',
				'no_found_rows'    => true,
				'suppress_filters' => true,
				'meta_key'         => '_affibloom_url_key', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- one lookup per add, a few hundred products.
				'meta_value'       => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- as above.
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
		return new Product(
			$post->ID,
			$post->post_title,
			AffiliateUrl::from_string( (string) get_post_meta( $post->ID, '_affibloom_url', true ) ),
			ProductType::tryFrom( (string) get_post_meta( $post->ID, '_affibloom_type', true ) ) ?? ProductType::Other
		);
	}
}
