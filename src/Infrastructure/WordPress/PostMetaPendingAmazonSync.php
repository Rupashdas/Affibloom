<?php
/**
 * The Amazon products that still need Amazon's data, read from post meta and the stored rows.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Domain\Amazon\Marketplace;
use Affibloom\Domain\Amazon\PendingAmazonSync;

/**
 * The pending set of bulk sync in one query (Lasso_DB::get_amazon_lasso_post_ids_pending_sync, R40), on Affibloom's
 * product meta and `{prefix}affibloom_amazon_products`. The stored row is matched on the ASIN and the product's store
 * (Lasso matches the ASIN alone); a product without a store uses Lasso's default, as AmazonDataService does. Lasso's
 * third arm, a missing `amazon_product_last_updated`, has no counterpart: a stored row with a name is the record that
 * Affibloom has the product's data (progress.md deviation 116).
 */
final class PostMetaPendingAmazonSync implements PendingAmazonSync {

	/**
	 * Their IDs, lowest first.
	 *
	 * @param int $limit At most this many; 0 for all.
	 * @return list<int>
	 */
	public function ids( int $limit = 0 ): array {
		global $wpdb;

		$sql    = "SELECT DISTINCT p.ID FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} t ON t.post_id = p.ID AND t.meta_key = '_affibloom_type' AND t.meta_value = 'amazon'
			LEFT JOIN {$wpdb->postmeta} a ON a.post_id = p.ID AND a.meta_key = '_affibloom_asin'
			LEFT JOIN {$wpdb->postmeta} c ON c.post_id = p.ID AND c.meta_key = '_affibloom_amazon_country'
			LEFT JOIN {$wpdb->postmeta} u ON u.post_id = p.ID AND u.meta_key = '_affibloom_url'
			LEFT JOIN %i ap ON ap.asin = a.meta_value AND ap.country = COALESCE( NULLIF( c.meta_value, '' ), %s )
			WHERE p.post_type = %s AND p.post_status = 'publish'
				AND (
					( a.meta_value <> '' AND ( ap.asin IS NULL OR ap.name = '' ) )
					OR ( ( a.meta_value IS NULL OR a.meta_value = '' ) AND (
						u.meta_value LIKE %s OR u.meta_value LIKE %s OR u.meta_value LIKE %s
						OR u.meta_value LIKE %s OR u.meta_value LIKE %s
					) )
				)
			ORDER BY p.ID ASC";
		$values = array(
			$wpdb->prefix . TableAmazonProductRepository::TABLE,
			Marketplace::DEFAULT_COUNTRY,
			ProductPostType::NAME,
			'%amzn.to%',
			'%amzn.com%',
			'%amazon.%/dp/%',
			'%amazon.%/gp/product/%',
			'%amazon.%/ASIN/%',
		);
		if ( $limit > 0 ) {
			$sql     .= ' LIMIT %d';
			$values[] = $limit;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- prepared here; the set changes with every add and sync.
		$ids = $wpdb->get_col( $wpdb->prepare( $sql, ...$values ) );

		return array_map( 'intval', $ids );
	}
}
