<?php
/**
 * Products in the classic editor's link dialog.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Domain\Product\LinkKind;
use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Domain\Product\SearchTerm;
use Affibloom\Interface\PublicLinks;
use Affibloom\Module;

/**
 * The classic "Insert/edit link" search lists products with their public link (F24 R28, D18; Task CL9), as Lasso's
 * `wp_link_query` filter does (class-post-type.php 29-45). WordPress labels each result by the public post types, so
 * the products are not added to its query (a private type would raise warnings there); they follow the posts on the
 * first page of a search, found as the Dashboard's search finds them, for someone who may edit posts.
 */
final class ClassicLinkSearch implements Module {

	/** How many products a search lists, as many as WordPress's page of posts. */
	private const LIMIT = 20;

	/**
	 * Builds the module.
	 *
	 * @param ProductRepository $products The products.
	 * @param PublicLinks       $links    Their public links.
	 */
	public function __construct( private ProductRepository $products, private PublicLinks $links ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_filter( 'wp_link_query', array( $this, 'results' ), 10, 2 );
	}

	/**
	 * The dialog's results with the products that match the search.
	 *
	 * @param mixed $results The results WordPress found.
	 * @param mixed $query   Its WP_Query arguments (`s`, `offset`).
	 * @return mixed
	 */
	public function results( mixed $results, mixed $query ): mixed {
		$search = is_array( $query ) && is_string( $query['s'] ?? null ) ? trim( $query['s'] ) : '';
		if ( '' === $search || 0 !== (int) ( $query['offset'] ?? 0 ) || ! current_user_can( 'edit_posts' ) ) {
			return $results;
		}

		$found = array();
		foreach ( $this->products->page( SearchTerm::from_input( $search ), LinkKind::All, 1, self::LIMIT )->items as $product ) {
			$found[] = array(
				'ID'        => (int) $product->id,
				'title'     => trim( esc_html( wp_strip_all_tags( $product->name ) ) ),
				'permalink' => $this->links->for( $product ),
				'info'      => __( 'Product', 'affibloom' ),
			);
		}

		return array_merge( is_array( $results ) ? $results : array(), $found );
	}
}
