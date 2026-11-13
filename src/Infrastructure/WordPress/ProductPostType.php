<?php
/**
 * The private post type products are stored in (ADR-001).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Module;

/**
 * The private post type products are stored in (ADR-001).
 */
final class ProductPostType implements Module {

	public const NAME = 'affibloom_product';

	/**
	 * The status of a product retired by the repoint (Task 17C; F1 requirements 11, X10, D41), Lasso's lasso_delete. Not
	 * the trash: WordPress deletes posts that wp_trash_post() trashed 30 days ago, and with them the pointer old boxes
	 * follow.
	 */
	public const RETIRED = 'affibloom_retired';

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_type' ) );
		add_action( 'init', array( $this, 'register_status' ) );
	}

	/**
	 * Registers the retired status as Lasso registers lasso_delete, but out of sight: no public view, no search, and
	 * not in core's lists (the post type has no core screens anyway).
	 */
	public function register_status(): void {
		register_post_status(
			self::RETIRED,
			array(
				'label'                     => _x( 'Retired', 'post status', 'affibloom' ),
				'public'                    => false,
				'internal'                  => true,
				'exclude_from_search'       => true,
				'show_in_admin_all_list'    => false,
				'show_in_admin_status_list' => false,
			)
		);
	}

	/**
	 * Registers the post type: no public URL and no core screens, because the plugin has its own.
	 */
	public function register_type(): void {
		register_post_type(
			self::NAME,
			array(
				'label'            => __( 'Products', 'affibloom' ),
				'public'           => false,
				'show_ui'          => false,
				'show_in_rest'     => false,
				'rewrite'          => false,
				'query_var'        => false,
				'supports'         => array( 'title' ),
				'can_export'       => true,
				'delete_with_user' => false,
				'capability_type'  => 'post',
				'map_meta_cap'     => true,
			)
		);
	}
}
