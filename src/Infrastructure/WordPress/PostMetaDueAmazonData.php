<?php
/**
 * The ASINs and stores whose stored Amazon data is due, from the products' meta and the Amazon products table.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Domain\Amazon\DueAmazonData;
use Affibloom\Domain\Amazon\Marketplace;

/**
 * The due ASIN and store pairs (C1 R38; C1Q4b): published Amazon products with an ASIN (as the bulk sync's pending
 * query reads them) whose row in `{prefix}affibloom_amazon_products` is missing or older than the cutoff, oldest first,
 * missing first. A pair shared by several products is one item, with one of their links.
 */
final class PostMetaDueAmazonData implements DueAmazonData {

	/**
	 * How many pairs are due.
	 *
	 * @param string $before The cutoff, "Y-m-d H:i:s" in UTC.
	 */
	public function count( string $before ): int {
		global $wpdb;

		[ $sql, $values ] = $this->query( $before );
		$sql              = 'SELECT COUNT(*) FROM ( ' . $sql . ' ) due';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- prepared here; read once an hour.
		return (int) $wpdb->get_var( $wpdb->prepare( $sql, ...$values ) );
	}

	/**
	 * The due pairs, oldest first.
	 *
	 * @param string $before The cutoff, "Y-m-d H:i:s" in UTC.
	 * @param int    $limit  At most this many.
	 * @return list<array{asin: string, country: string, link: string}>
	 */
	public function due( string $before, int $limit ): array {
		global $wpdb;

		if ( $limit < 1 ) {
			return array();
		}
		[ $sql, $values ] = $this->query( $before );
		$sql             .= ' ORDER BY updated IS NOT NULL, updated ASC LIMIT %d';
		$values[]         = $limit;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- prepared here; read once an hour.
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$values ), ARRAY_A );

		$due = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$due[] = array(
				'asin'    => (string) $row['asin'],
				'country' => (string) $row['country'],
				'link'    => (string) $row['link'],
			);
		}

		return $due;
	}

	/**
	 * The grouped query of due pairs and its values.
	 *
	 * @param string $before The cutoff.
	 * @return array{0: string, 1: list<string|int>}
	 */
	private function query( string $before ): array {
		global $wpdb;

		$sql = "SELECT UPPER( a.meta_value ) AS asin, COALESCE( NULLIF( c.meta_value, '' ), %s ) AS country,
				MIN( u.meta_value ) AS link, MIN( ap.last_updated ) AS updated
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} t ON t.post_id = p.ID AND t.meta_key = '_affibloom_type' AND t.meta_value = 'amazon'
			INNER JOIN {$wpdb->postmeta} a ON a.post_id = p.ID AND a.meta_key = '_affibloom_asin' AND a.meta_value <> ''
			LEFT JOIN {$wpdb->postmeta} c ON c.post_id = p.ID AND c.meta_key = '_affibloom_amazon_country'
			LEFT JOIN {$wpdb->postmeta} u ON u.post_id = p.ID AND u.meta_key = '_affibloom_url'
			LEFT JOIN %i ap ON ap.asin = a.meta_value AND ap.country = COALESCE( NULLIF( c.meta_value, '' ), %s )
			WHERE p.post_type = %s AND p.post_status = 'publish' AND ( ap.asin IS NULL OR ap.last_updated < %s )
			GROUP BY UPPER( a.meta_value ), COALESCE( NULLIF( c.meta_value, '' ), %s )";

		return array(
			$sql,
			array(
				Marketplace::DEFAULT_COUNTRY,
				$wpdb->prefix . TableAmazonProductRepository::TABLE,
				Marketplace::DEFAULT_COUNTRY,
				ProductPostType::NAME,
				$before,
				Marketplace::DEFAULT_COUNTRY,
			),
		);
	}
}
