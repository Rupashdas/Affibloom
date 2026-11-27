<?php
/**
 * Who else answers at a slug, from WordPress and the variants table.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Domain\Variant\SlugTaken;
use Affibloom\Domain\Variant\VariantKind;

/**
 * F27 R15, D7 against the stores: the variants table, posts and pages (published or drafts, as Lasso's check), and
 * products' slugs and old slugs (`_affibloom_old_slugs`, F24 N1), the saved product left out.
 */
final class WpSlugTaken implements SlugTaken {

	/**
	 * Builds the check.
	 *
	 * @param ProductRepository $products The products, for their slugs and old slugs.
	 */
	public function __construct( private ProductRepository $products ) {}

	/**
	 * Whether another product has a permalink variant with this key. A variant whose product no longer exists (deleted
	 * outside WordPress, so its rows stayed) blocks nothing: it answers no address either (V2 edge case 3). A trashed
	 * product keeps its keys, since it can come back.
	 *
	 * @param string $key        The key.
	 * @param int    $product_id The product whose variants are saved.
	 */
	public function by_other_variant( string $key, int $product_id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Affibloom's own table, once per saved row.
		return null !== $wpdb->get_var( $wpdb->prepare( 'SELECT v.id FROM %i AS v INNER JOIN %i AS p ON p.ID = v.parent_id AND p.post_type = %s WHERE v.variant_key = %s AND v.variant_kind = %s AND v.parent_id <> %d LIMIT 1', $wpdb->prefix . TableVariantRepository::TABLE, $wpdb->posts, ProductPostType::NAME, $key, VariantKind::Permalink->value, $product_id ) );
	}

	/**
	 * Whether a post or a page, published or a draft, has this slug.
	 *
	 * @param string $key The key.
	 */
	public function by_post( string $key ): bool {
		return array() !== get_posts(
			array(
				'post_type'        => array( 'post', 'page' ),
				'post_status'      => array( 'publish', 'draft' ),
				'name'             => $key,
				'fields'           => 'ids',
				'posts_per_page'   => 1,
				'no_found_rows'    => true,
				'suppress_filters' => true, // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.SuppressFilters_suppress_filters -- the store as it is: a filter that hides a post would let two addresses answer as one.
			)
		);
	}

	/**
	 * Whether another product has this slug, now or before.
	 *
	 * @param string $key    The key.
	 * @param int    $product_id The product whose variants are saved.
	 */
	public function by_other_product( string $key, int $product_id ): bool {
		$now    = $this->products->find_by_slug( $key );
		$before = $this->products->find_by_old_slug( $key );

		return ( null !== $now && $product_id !== $now->id ) || ( null !== $before && $product_id !== $before->id );
	}
}
