<?php
/**
 * The comparison tables' post type.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Module;

/**
 * `affibloom_table` (F25 D1; Task T1): not public, no UI, no REST, no rewrite; the title is the table's name, the meta
 * `_affibloom_table` the rest of it.
 */
final class TablePostType implements Module {

	public const NAME = 'affibloom_table';

	/** The meta that holds the table. */
	public const META = '_affibloom_table';

	/**
	 * Builds the post type.
	 *
	 * @param string $capability The capability that may change tables (`Capabilities::PRODUCTS`); every capability of
	 *                           the type is this one (Task 37A, finding S1).
	 */
	public function __construct( private string $capability ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_type' ) );
	}

	/**
	 * Registers the post type.
	 */
	public function register_type(): void {
		register_post_type(
			self::NAME,
			array(
				'label'        => __( 'Tables', 'affibloom' ),
				'public'       => false,
				'show_ui'      => false,
				'show_in_rest' => false,
				'rewrite'      => false,
				'query_var'    => false,
				'supports'     => array( 'title' ),
				'can_export'   => true,
				'capabilities' => PostTypeCapabilities::only( $this->capability ),
				'map_meta_cap' => true,
			)
		);
	}
}
