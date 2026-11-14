<?php
/**
 * The taxonomy groups are stored in (ADR-001, F23 D1).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Module;

/**
 * The taxonomy groups are stored in: a group is a term of `affibloom_group` on the product post type, as Lasso's
 * groups are terms of `lasso-cat` (F23 D1). It has no public side and no core screens; the plugin has its own.
 */
final class GroupTaxonomy implements Module {

	public const NAME = 'affibloom_group';

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		// After the post type (ProductPostType registers on init at priority 10), so the object type exists.
		add_action( 'init', array( $this, 'register_taxonomy' ), 11 );
	}

	/**
	 * Registers the taxonomy: not public, not hierarchical, no core UI, no REST, no address.
	 */
	public function register_taxonomy(): void {
		register_taxonomy(
			self::NAME,
			ProductPostType::NAME,
			array(
				'label'             => __( 'Groups', 'affibloom' ),
				'public'            => false,
				'hierarchical'      => false,
				'show_ui'           => false,
				'show_in_nav_menus' => false,
				'show_in_rest'      => false,
				'show_admin_column' => false,
				'rewrite'           => false,
				'query_var'         => false,
				'capabilities'      => array(
					'manage_terms' => 'manage_options',
					'edit_terms'   => 'manage_options',
					'delete_terms' => 'manage_options',
					'assign_terms' => 'manage_options',
				),
			)
		);
	}
}
