<?php
/**
 * The detected affiliate links' table.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Domain\Monetize\DetectedLink;
use Affibloom\Domain\Monetize\DetectedLinkRepository;

/**
 * `{prefix}affibloom_detected_links` (F35 D4, R11; Task C2B): Lasso's `lasso_auto_monetize` columns under Affibloom's
 * names, unique by the URL's MD5. Its network is the stored display name (R7, "Commission Junction"), wider than
 * Lasso's 20 characters.
 */
final class TableDetectedLinkRepository implements DetectedLinkRepository {

	/** The table, without the prefix. */
	public const TABLE = 'affibloom_detected_links';

	/** Its definition, as dbDelta reads it (Infrastructure\Schema\Table). */
	public const COLUMNS = "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
url text NOT NULL,
url_hash char(32) NOT NULL,
network varchar(255) NOT NULL DEFAULT '',
affiliate_id varchar(64) NOT NULL DEFAULT '',
advertiser_id varchar(64) NOT NULL DEFAULT '',
deep_link varchar(1024) NOT NULL DEFAULT '',
domain varchar(100) NOT NULL DEFAULT '',
product_id bigint(20) unsigned NOT NULL DEFAULT 0,
detected_at datetime NOT NULL,
PRIMARY KEY  (id),
UNIQUE KEY url_hash (url_hash),
KEY product_id (product_id)";

	/**
	 * Records a link once per URL; ties a row without a product to the one given.
	 *
	 * @param string       $url        The link as written.
	 * @param DetectedLink $link       What it carries.
	 * @param int          $product_id The product it already is; 0 when none.
	 */
	public function record( string $url, DetectedLink $link, int $product_id ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Affibloom's own table, one row per link of a saved post.
		$wpdb->query(
			$wpdb->prepare(
				'INSERT INTO %i (url, url_hash, network, affiliate_id, advertiser_id, deep_link, domain, product_id, detected_at) VALUES (%s, %s, %s, %s, %s, %s, %s, %d, %s)
				ON DUPLICATE KEY UPDATE product_id = IF( product_id = 0, VALUES(product_id), product_id )',
				$wpdb->prefix . self::TABLE,
				$url,
				md5( $url ),
				$link->network->integration,
				substr( $link->affiliate_id, 0, 64 ),
				substr( $link->advertiser_id, 0, 64 ),
				substr( $link->deep_link, 0, 1024 ),
				substr( $link->domain, 0, 100 ),
				max( 0, $product_id ),
				gmdate( 'Y-m-d H:i:s' )
			)
		);
	}

	/**
	 * Rows still without a published product, oldest first.
	 *
	 * @param int $limit At most this many.
	 * @return array<int, string> The link, by row ID.
	 */
	public function without_product( int $limit ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Affibloom's own table, one background run.
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT d.id, d.url FROM %i AS d LEFT JOIN %i AS p ON p.ID = d.product_id AND p.post_type = %s AND p.post_status = 'publish'
				WHERE d.product_id = 0 OR p.ID IS NULL ORDER BY d.id LIMIT %d",
				$wpdb->prefix . self::TABLE,
				$wpdb->posts,
				ProductPostType::NAME,
				max( 1, $limit )
			),
			ARRAY_A
		);
		$found = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$found[ (int) $row['id'] ] = (string) $row['url'];
		}

		return $found;
	}

	/**
	 * Records the product made for a row.
	 *
	 * @param int $id         The row.
	 * @param int $product_id The product.
	 */
	public function set_product( int $id, int $product_id ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Affibloom's own table, one row.
		$wpdb->update( $wpdb->prefix . self::TABLE, array( 'product_id' => $product_id ), array( 'id' => $id ), array( '%d' ), array( '%d' ) );
	}

	/**
	 * The product recorded for a link; 0 when none.
	 *
	 * @param string $url The link as written.
	 */
	public function product_for( string $url ): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Affibloom's own table, by its unique key; the resolver keeps the answer for the run.
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT product_id FROM %i WHERE url_hash = %s', $wpdb->prefix . self::TABLE, md5( $url ) ) );
	}
}
