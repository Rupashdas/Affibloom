<?php
/**
 * Products in the block editor's link search.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Infrastructure\WordPress\ProductPostType;
use Affibloom\Interface\PublicLinks;
use WP_REST_Post_Search_Handler;
use WP_REST_Request;
use WP_REST_Search_Controller;

/**
 * WordPress's post search with Affibloom's products in it (F24 R28, D18; Task CL8). Gutenberg's link popover asks
 * `/wp/v2/search` for the types `post`, `term` and `post-format` only, so a search type of its own would never be asked;
 * this handler takes the place of the `post` one. Lasso gets the same by a public post type and a rewrite of the
 * response's URLs (class-post-type.php 54-69).
 *
 * Products are found only for someone who may edit posts (the search route is open to every visitor, and a product's
 * destination is not public). A product's result links to its public link and reads "Product".
 */
final class ProductSearchHandler extends WP_REST_Post_Search_Handler {

	/**
	 * Builds the handler.
	 *
	 * @param PublicLinks       $links    The products' public links.
	 * @param ProductRepository $products The products.
	 */
	public function __construct( private PublicLinks $links, private ProductRepository $products ) {
		parent::__construct();
		$this->subtypes[] = ProductPostType::NAME;
	}

	/**
	 * Searches the posts, and the products for an editor.
	 *
	 * @param WP_REST_Request $request The search request.
	 * @return array{ids: list<int>, total: int}
	 *
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 */
	public function search_items( WP_REST_Request $request ) {
		$editor  = current_user_can( 'edit_posts' );
		$exclude = static function ( array $args ) use ( $editor ): array {
			$types = (array) $args['post_type'];
			if ( ! $editor ) {
				$args['post_type'] = array_values( array_diff( $types, array( ProductPostType::NAME ) ) );
			}

			return $args;
		};
		add_filter( 'rest_post_search_query', $exclude, 1 );
		try {
			// An empty list would make WP_Query search every type: an anonymous search for products alone finds none.
			$asked = (array) $request[ WP_REST_Search_Controller::PROP_SUBTYPE ];
			if ( ! $editor && array( ProductPostType::NAME ) === $asked ) {
				return array(
					self::RESULT_IDS   => array(),
					self::RESULT_TOTAL => 0,
				);
			}

			return parent::search_items( $request );
		} finally {
			remove_filter( 'rest_post_search_query', $exclude, 1 );
		}
	}

	/**
	 * One result: a product's with its public link and "Product".
	 *
	 * @param int           $id     The post's ID.
	 * @param array<string> $fields The fields asked for.
	 * @return array<string, mixed>
	 */
	public function prepare_item( $id, array $fields ) {
		$data = parent::prepare_item( $id, $fields );
		if ( ProductPostType::NAME !== get_post_type( $id ) ) {
			return $data;
		}
		$product = $this->products->get( (int) $id );
		if ( null !== $product && isset( $data[ WP_REST_Search_Controller::PROP_URL ] ) ) {
			$data[ WP_REST_Search_Controller::PROP_URL ] = $this->links->for( $product );
		}
		if ( isset( $data[ WP_REST_Search_Controller::PROP_SUBTYPE ] ) ) {
			$data[ WP_REST_Search_Controller::PROP_SUBTYPE ] = __( 'Product', 'affibloom' );
		}

		return $data;
	}
}
