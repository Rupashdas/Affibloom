<?php
/**
 * Whether another kind of post uses a product's slug.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

/**
 * Lasso's conflict check on save (F24 R5, N7): a post of any other type (not a menu item, not an attachment) with the
 * product's slug takes its address, since a post or page wins (R16). Another product's slug is no conflict: WordPress
 * has already made the product's slug unique.
 */
final class WpSlugConflicts {

	/**
	 * The post that uses the slug, as its type's name and its edit address, or null.
	 *
	 * @param string $slug A product's slug.
	 * @return array{type: string, edit: string}|null
	 */
	public function used_by( string $slug ): ?array {
		if ( '' === $slug ) {
			return null;
		}
		$posts = get_posts(
			array(
				'post_type'        => 'any',
				'name'             => $slug,
				'post_status'      => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'posts_per_page'   => 5,
				'no_found_rows'    => true,
				'suppress_filters' => true, // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.SuppressFilters_suppress_filters -- the store as it is: a filter that hides a post would let two addresses answer as one.
			)
		);
		foreach ( $posts as $post ) {
			if ( in_array( $post->post_type, array( ProductPostType::NAME, 'nav_menu_item', 'attachment' ), true ) ) {
				continue;
			}
			$type = get_post_type_object( $post->post_type );

			return array(
				'type' => null !== $type ? (string) $type->labels->singular_name : $post->post_type,
				'edit' => (string) get_edit_post_link( $post->ID, 'raw' ),
			);
		}

		return null;
	}
}
