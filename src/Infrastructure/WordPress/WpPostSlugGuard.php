<?php
/**
 * Whether a path belongs to the site's own posts or terms.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

/**
 * Lasso's guards before a cloaked redirect (F24 R16, R17): a post or page, published or draft, with the slug is shown
 * instead; with the prefix `tag` or `category`, an existing term's path is never a product.
 */
final class WpPostSlugGuard {

	/**
	 * Whether a post or page (published or draft) has this slug.
	 *
	 * @param string $slug The slug.
	 */
	public function post_has( string $slug ): bool {
		$ids = get_posts(
			array(
				'post_type'        => array( 'post', 'page' ),
				'name'             => $slug,
				'post_status'      => array( 'publish', 'draft' ),
				'posts_per_page'   => 1,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => true, // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.SuppressFilters_suppress_filters -- the store as it is: a filter that hides a post would let two addresses answer as one.
			)
		);

		return array() !== $ids;
	}

	/**
	 * Whether `/<prefix>/<slug>/` is an existing tag's or category's address.
	 *
	 * @param string $prefix The Cloaked Link Prefix.
	 * @param string $slug   The slug.
	 */
	public function term_path( string $prefix, string $slug ): bool {
		$taxonomy = array(
			'tag'      => 'post_tag',
			'category' => 'category',
		)[ $prefix ] ?? '';

		return '' !== $taxonomy && null !== term_exists( $slug, $taxonomy );
	}
}
