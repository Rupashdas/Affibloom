<?php
/**
 * Every product's domain, once.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Domain\Opportunity\BaseDomain;

/**
 * Fills `_affibloom_domain` for the products stored before Task O1 wrote it on every save (F22 D5): the base domain of
 * each product's URL (an Amazon short link's is Amazon's), whatever its status. Schema steps run it: once for Task O1,
 * again when Amazon short links became Amazon's (F22 R71).
 */
final class ProductDomainBackfill {

	/**
	 * Writes every product's domain.
	 */
	public static function run(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- a one-time upgrade step over a few hundred products.
		$urls = $wpdb->get_results( $wpdb->prepare( "SELECT p.ID, m.meta_value AS url FROM %i p JOIN %i m ON m.post_id = p.ID AND m.meta_key = '_affibloom_url' WHERE p.post_type = %s", $wpdb->posts, $wpdb->postmeta, ProductPostType::NAME ), ARRAY_A );
		foreach ( is_array( $urls ) ? $urls : array() as $row ) {
			update_post_meta( (int) $row['ID'], '_affibloom_domain', BaseDomain::of_product( (string) $row['url'] ) );
		}
	}
}
