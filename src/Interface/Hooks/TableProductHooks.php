<?php
/**
 * A product deleted for good leaves its comparison tables.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Hooks;

use Affibloom\Application\Tables\TableService;
use Affibloom\Infrastructure\WordPress\ProductPostType;
use Affibloom\Module;
use WP_Post;

/**
 * F25 R60, D15 (Task T17): when a product's post is deleted for good (the product page's Delete, emptying the trash,
 * WP-CLI), every table drops it and its values, so no table keeps an orphan (Lasso left its cells behind). Trashing
 * keeps it: a trashed product can come back.
 */
final class TableProductHooks implements Module {

	/**
	 * Builds the hooks.
	 *
	 * @param TableService $tables The tables.
	 */
	public function __construct( private TableService $tables ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'deleted_post', array( $this, 'deleted' ), 10, 2 );
	}

	/**
	 * A post deleted for good: a product leaves every table.
	 *
	 * @param int          $post_id The post.
	 * @param WP_Post|null $post    The post as it was.
	 */
	public function deleted( int $post_id, ?WP_Post $post = null ): void {
		if ( null !== $post && ProductPostType::NAME === $post->post_type ) {
			$this->tables->remove_product_everywhere( $post_id );
		}
	}
}
