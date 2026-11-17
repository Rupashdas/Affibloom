<?php
/**
 * Puts products into the block editor's link search.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Interface\PublicLinks;
use Affibloom\Module;
use WP_REST_Post_Search_Handler;
use WP_REST_Search_Handler;

/**
 * Swaps WordPress's post search handler for ProductSearchHandler (F24 R28; Task CL8). The handler is made when the
 * search route is, after the post types exist.
 */
final class ProductLinkSearch implements Module {

	/**
	 * Builds the module.
	 *
	 * @param PublicLinks       $links    The products' public links.
	 * @param ProductRepository $products The products.
	 */
	public function __construct( private PublicLinks $links, private ProductRepository $products ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_filter( 'wp_rest_search_handlers', array( $this, 'handlers' ) );
	}

	/**
	 * The search handlers, WordPress's own post handler replaced; one another plugin replaced is left as it is.
	 *
	 * @param mixed $handlers The search handlers.
	 * @return mixed
	 */
	public function handlers( mixed $handlers ): mixed {
		if ( ! is_array( $handlers ) ) {
			return $handlers;
		}

		return array_map(
			fn( $handler ) => $handler instanceof WP_REST_Search_Handler && WP_REST_Post_Search_Handler::class === $handler::class ? new ProductSearchHandler( $this->links, $this->products ) : $handler,
			$handlers
		);
	}
}
