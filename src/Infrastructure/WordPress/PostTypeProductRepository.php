<?php
/**
 * Products as affibloom_product posts with _affibloom_* meta.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductRepository;
use RuntimeException;

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
				'_affibloom_url'  => $product->url->value(),
				'_affibloom_type' => $product->type->value,
			),
		);

		// wp_insert_post() unslashes what it is given, so a link's backslashes survive only if slashed first.
		$id = wp_insert_post( wp_slash( $post ), true );
		if ( is_wp_error( $id ) ) {
			throw new RuntimeException( esc_html( $id->get_error_message() ) );
		}

		return $product->with( array( 'id' => $id ) );
	}
}
