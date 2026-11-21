<?php
/**
 * The click counts in {prefix}affibloom_clicks.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Domain\Clicks\Button;
use Affibloom\Domain\Clicks\ClickStore;

/**
 * `{prefix}affibloom_clicks` (F12 D2, ADR-005 with `location_id`; Task 33A): one row per UTC day, product, host post,
 * location and button, holding that day's total. A click is one atomic upsert, so two readers clicking in the same
 * second are both counted (edge case 3). Rows older than 400 days go with the daily job (Task 33C).
 */
final class TableClickStore implements ClickStore {

	/** The table's name without the site's prefix. */
	public const TABLE = 'affibloom_clicks';

	/** Its definition, as dbDelta reads it (Infrastructure\Schema\Table). */
	public const COLUMNS = 'day date NOT NULL,
product_id bigint(20) unsigned NOT NULL,
post_id bigint(20) unsigned NOT NULL DEFAULT 0,
location_id bigint(20) unsigned NOT NULL DEFAULT 0,
button varchar(8) NOT NULL,
clicks int(10) unsigned NOT NULL DEFAULT 0,
PRIMARY KEY  (day,product_id,post_id,location_id,button),
KEY product_day (product_id,day),
KEY location_day (location_id,day)';

	/**
	 * Adds one click to its day's total.
	 *
	 * @param string $day        The UTC day, "Y-m-d".
	 * @param int    $product_id The product.
	 * @param int    $post_id    The post; 0 when none.
	 * @param int    $location   The link index row; 0 when none.
	 * @param Button $button     What was clicked.
	 */
	public function add( string $day, int $product_id, int $post_id, int $location, Button $button ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Affibloom's own table, one upsert per click.
		$wpdb->query( $wpdb->prepare( 'INSERT INTO %i (day, product_id, post_id, location_id, button, clicks) VALUES (%s, %d, %d, %d, %s, 1) ON DUPLICATE KEY UPDATE clicks = clicks + 1', $wpdb->prefix . self::TABLE, $day, $product_id, $post_id, $location, $button->value ) );
	}

	/**
	 * A product's clicks from a day on as an SQL value (the Clicks sort, Task 13K; F19 R6 `IFNULL(clicks, 0)`).
	 *
	 * @param string $product The SQL expression of the product's ID (a column of the outer query).
	 * @param string $day     The first UTC day counted, "Y-m-d".
	 */
	public static function total_sql( string $product, string $day ): string {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $product is a column of the caller's query, never input.
		return $wpdb->prepare( "( SELECT COALESCE(SUM(afk.clicks), 0) FROM %i AS afk WHERE afk.product_id = {$product} AND afk.day >= %s )", $wpdb->prefix . self::TABLE, $day );
	}

	/**
	 * Each product's clicks from a day on, in one query (the product_day index).
	 *
	 * @param string    $day         The first UTC day counted, "Y-m-d".
	 * @param list<int> $product_ids The products.
	 * @return array<int, int> By product ID; a product without clicks is left out.
	 */
	public function totals_since( string $day, array $product_ids ): array {
		global $wpdb;

		$ids = implode( ',', array_map( 'intval', $product_ids ) );
		if ( '' === $ids ) {
			return array();
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Affibloom's own table, one query per list page; the IDs are integers.
		$found  = $wpdb->get_results( $wpdb->prepare( "SELECT product_id, SUM(clicks) AS clicks FROM %i WHERE day >= %s AND product_id IN ({$ids}) GROUP BY product_id", $wpdb->prefix . self::TABLE, $day ), ARRAY_A );
		$totals = array();
		foreach ( is_array( $found ) ? $found : array() as $row ) {
			$totals[ (int) $row['product_id'] ] = (int) $row['clicks'];
		}

		return $totals;
	}

	/**
	 * All published products' clicks from a day on: a trashed or retired product's rows are not counted (U9).
	 *
	 * @param string $day The first UTC day counted, "Y-m-d".
	 */
	public function total_since( string $day ): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- one sum for the Dashboard's head.
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(c.clicks), 0) FROM %i AS c JOIN %i AS p ON p.ID = c.product_id AND p.post_type = %s AND p.post_status = %s WHERE c.day >= %s', $wpdb->prefix . self::TABLE, $wpdb->posts, ProductPostType::NAME, 'publish', $day ) );
	}

	/**
	 * Each location's clicks from a day on, in one query (the location_day index).
	 *
	 * @param string    $day          The first UTC day counted, "Y-m-d".
	 * @param list<int> $location_ids The link index rows.
	 * @return array<int, int> By location ID; a location without clicks is left out.
	 */
	public function by_location( string $day, array $location_ids ): array {
		global $wpdb;

		$ids = implode( ',', array_filter( array_map( 'intval', $location_ids ) ) );
		if ( '' === $ids ) {
			return array();
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Affibloom's own table, one query per tab page; the IDs are integers.
		$found  = $wpdb->get_results( $wpdb->prepare( "SELECT location_id, SUM(clicks) AS clicks FROM %i WHERE day >= %s AND location_id IN ({$ids}) GROUP BY location_id", $wpdb->prefix . self::TABLE, $day ), ARRAY_A );
		$totals = array();
		foreach ( is_array( $found ) ? $found : array() as $row ) {
			$totals[ (int) $row['location_id'] ] = (int) $row['clicks'];
		}

		return $totals;
	}

	/**
	 * Removes the rows of days before this one.
	 *
	 * @param string $day The first UTC day kept, "Y-m-d".
	 */
	public function delete_before( string $day ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Affibloom's own table, once a day.
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE day < %s', $wpdb->prefix . self::TABLE, $day ) );
	}
}
