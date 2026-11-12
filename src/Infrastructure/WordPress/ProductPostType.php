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
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_type' ) );
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
