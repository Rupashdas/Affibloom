<?php
/**
 * The capabilities of Affibloom's post types, all one capability (Task 37A, finding S1).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

/**
 * The capabilities of Affibloom's post types, all one capability (Task 37A, finding S1). With `capability_type`
 * 'post' an Editor passes `edit_post` and `delete_post` on a product or a table and could change or trash it through
 * WordPress's own paths (Quick Edit's admin-ajax, XML-RPC), which never ask Affibloom. Mapped to the capability that
 * uses Affibloom, `map_meta_cap` turns every per-post check into that one.
 */
final class PostTypeCapabilities {

	/**
	 * Every primitive capability of a post type, each the given one.
	 *
	 * @param string $capability The capability that may use Affibloom.
	 * @return array<string, string>
	 */
	public static function only( string $capability ): array {
		$primitives = array(
			'edit_posts',
			'edit_others_posts',
			'edit_published_posts',
			'edit_private_posts',
			'publish_posts',
			'read_private_posts',
			'delete_posts',
			'delete_others_posts',
			'delete_published_posts',
			'delete_private_posts',
			'create_posts',
		);

		return array_fill_keys( $primitives, $capability );
	}
}
