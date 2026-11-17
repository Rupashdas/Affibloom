<?php
/**
 * The link index in {prefix}affibloom_link_locations.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Domain\LinkIndex\LinkIndexStats;
use Affibloom\Domain\LinkIndex\LinkLocation;
use Affibloom\Domain\LinkIndex\LinkLocationRepository;
use Affibloom\Domain\LinkIndex\LocationKey;
use RuntimeException;

/**
 * `{prefix}affibloom_link_locations` (F21 D1): Lasso's link_locations columns in Affibloom's names, without Lasso's
 * stamp column (deviation 146: the content holds no Lasso IDs after the migration), with `assigned_product_id` (F22
 * N1-A). A host's rows are replaced in one transaction; a row with the same key keeps its first URL, its dismissal and
 * its assignment (D10). Indexes: Lasso's lookups by product, host, domain and display type, and the opportunity cover.
 */
final class TableLinkLocationRepository implements LinkLocationRepository {

	/** The table's name without the site's prefix. */
	public const TABLE = 'affibloom_link_locations';

	/** Its definition, as dbDelta reads it (Infrastructure\Schema\Table). */
	public const COLUMNS = "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
host_id bigint(20) unsigned NOT NULL,
host_url varchar(1000) NOT NULL DEFAULT '',
source varchar(40) NOT NULL DEFAULT 'content',
occurrence int(11) NOT NULL DEFAULT 1,
product_id bigint(20) unsigned NOT NULL DEFAULT 0,
assigned_product_id bigint(20) unsigned NOT NULL DEFAULT 0,
link_type varchar(20) NOT NULL,
display_type varchar(50) NOT NULL,
display_ref varchar(40) DEFAULT NULL,
anchor_text varchar(1500) DEFAULT NULL,
url text NOT NULL,
original_url text NOT NULL,
domain varchar(150) DEFAULT NULL,
url_md5 char(32) NOT NULL,
asin varchar(150) DEFAULT NULL,
tracking_id varchar(150) DEFAULT NULL,
country char(2) NOT NULL DEFAULT 'us',
no_follow tinyint(1) NOT NULL DEFAULT 0,
new_window tinyint(1) NOT NULL DEFAULT 0,
is_ignored tinyint(1) NOT NULL DEFAULT 0,
affiliate_platform varchar(64) DEFAULT NULL,
detected_at datetime NOT NULL,
PRIMARY KEY  (id),
UNIQUE KEY location (host_id,source,url_md5,occurrence),
KEY product_id (product_id),
KEY product_host (product_id,host_id),
KEY opportunity (product_id,is_ignored,domain),
KEY assigned_product_id (assigned_product_id),
KEY domain (domain),
KEY display_type (display_type)";

	/**
	 * Replaces a host's rows, keeping what a rescan keeps (D10).
	 *
	 * @param int                $host_id The host post.
	 * @param list<LinkLocation> $rows    Its rows now.
	 * @throws RuntimeException When the database refuses the write; the host's old rows stay.
	 */
	public function replace_host( int $host_id, array $rows ): void {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Affibloom's own table, written once per indexed post.
		$wpdb->query( 'START TRANSACTION' );
		$stored = $wpdb->get_results( $wpdb->prepare( 'SELECT source, url_md5, occurrence, original_url, is_ignored, assigned_product_id FROM %i WHERE host_id = %d', $table, $host_id ), ARRAY_A );
		$kept   = array();
		foreach ( is_array( $stored ) ? $stored : array() as $row ) {
			$kept[ LocationKey::of( $host_id, (string) $row['source'], (string) $row['url_md5'], (int) $row['occurrence'] ) ] = $row;
		}

		$ok = false !== $wpdb->delete( $table, array( 'host_id' => $host_id ), array( '%d' ) );
		foreach ( $rows as $row ) {
			if ( ! $ok ) {
				break;
			}
			$old = $kept[ $row->key() ] ?? null;
			if ( null !== $old ) {
				$row = $row->keeping( (string) $old['original_url'], '1' === (string) $old['is_ignored'], (int) $old['assigned_product_id'] );
			}
			$ok = false !== $wpdb->insert( $table, self::values( $row ), self::FORMATS );
		}
		if ( ! $ok ) {
			$error = $wpdb->last_error;
			$wpdb->query( 'ROLLBACK' );
			throw new RuntimeException( "The link index could not be written for post {$host_id}: {$error}" ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- escaped where it is printed (WP-CLI).
		}
		$wpdb->query( 'COMMIT' );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Removes a host's rows.
	 *
	 * @param int $host_id The host post.
	 */
	public function delete_host( int $host_id ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Affibloom's own table.
		$wpdb->delete( $wpdb->prefix . self::TABLE, array( 'host_id' => $host_id ), array( '%d' ) );
	}

	/**
	 * Pins a product's rows to it.
	 *
	 * @param int $product_id The product.
	 */
	public function pin_product( int $product_id ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Affibloom's own table, on a product's save.
		$wpdb->query( $wpdb->prepare( 'UPDATE %i SET assigned_product_id = %d WHERE product_id = %d', $wpdb->prefix . self::TABLE, $product_id, $product_id ) );
	}

	/**
	 * Moves a retired product's rows (tied or pinned to it) to the product it points to, pinned there.
	 *
	 * @param int $from The retired product.
	 * @param int $to   The product it points to.
	 */
	public function move_product( int $from, int $to ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Affibloom's own table, on a repoint.
		$wpdb->query( $wpdb->prepare( 'UPDATE %i SET product_id = %d, assigned_product_id = %d WHERE product_id = %d OR assigned_product_id = %d', $wpdb->prefix . self::TABLE, $to, $to, $from, $from ) );
	}

	/**
	 * Every host post that has rows.
	 *
	 * @return list<int>
	 */
	public function host_ids(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- the daily pass, once a day.
		return array_map( 'intval', $wpdb->get_col( $wpdb->prepare( 'SELECT DISTINCT host_id FROM %i', $wpdb->prefix . self::TABLE ) ) );
	}

	/**
	 * The whole index's figures, in two queries.
	 */
	public function stats(): LinkIndexStats {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- figures for a status line, read on request.
		$totals = $wpdb->get_row( $wpdb->prepare( 'SELECT COUNT(DISTINCT host_id) AS hosts, COUNT(*) AS links, SUM(product_id > 0) AS tied, COUNT(DISTINCT NULLIF(product_id, 0)) AS products, MAX(detected_at) AS latest FROM %i', $table ), ARRAY_A );
		$types  = $wpdb->get_results( $wpdb->prepare( 'SELECT link_type, COUNT(*) AS n FROM %i GROUP BY link_type', $table ), ARRAY_A );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$by_type = array();
		foreach ( is_array( $types ) ? $types : array() as $type ) {
			$by_type[ (string) $type['link_type'] ] = (int) $type['n'];
		}
		$totals = is_array( $totals ) ? $totals : array();

		return new LinkIndexStats( (int) ( $totals['hosts'] ?? 0 ), (int) ( $totals['links'] ?? 0 ), (int) ( $totals['tied'] ?? 0 ), (int) ( $totals['products'] ?? 0 ), $by_type, (string) ( $totals['latest'] ?? '' ) );
	}

	/** The insert's formats, in values()' order. */
	private const FORMATS = array( '%d', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s' );

	/**
	 * A row as the table's columns.
	 *
	 * @param LinkLocation $row The row.
	 * @return array<string, int|string|null>
	 */
	private static function values( LinkLocation $row ): array {
		return array(
			'host_id'             => $row->host_id,
			'host_url'            => mb_strcut( $row->host_url, 0, 1000, 'UTF-8' ),
			'source'              => $row->source,
			'occurrence'          => $row->occurrence,
			'product_id'          => $row->product_id,
			'assigned_product_id' => $row->assigned_product_id,
			'link_type'           => $row->link_type,
			'display_type'        => $row->display_type,
			'display_ref'         => $row->display_ref,
			'anchor_text'         => $row->anchor_text,
			'url'                 => $row->url,
			'original_url'        => '' !== $row->original_url ? $row->original_url : $row->url,
			'domain'              => $row->domain,
			'url_md5'             => $row->url_md5,
			'asin'                => null !== $row->asin ? substr( $row->asin, 0, 150 ) : null,
			'tracking_id'         => null !== $row->tracking_id ? substr( $row->tracking_id, 0, 150 ) : null,
			'country'             => substr( $row->country, 0, 2 ),
			'no_follow'           => (int) $row->no_follow,
			'new_window'          => (int) $row->new_window,
			'is_ignored'          => (int) $row->is_ignored,
			'affiliate_platform'  => $row->affiliate_platform,
			'detected_at'         => $row->detected_at,
		);
	}

	/**
	 * A host's links tied to a product and not dismissed, by their key, with the row's ID and its product (an editor's
	 * assignment first; Task 35H).
	 *
	 * @param int $host_id The post.
	 * @return array<string, array{id: int, product: int}>
	 */
	public function tied_links( int $host_id ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Affibloom's own table, once per printed post (RenderedLinks keeps it for the request).
		$found = $wpdb->get_results( $wpdb->prepare( 'SELECT id, source, url_md5, occurrence, product_id, assigned_product_id FROM %i WHERE host_id = %d AND is_ignored = 0 AND ( product_id > 0 OR assigned_product_id > 0 )', $wpdb->prefix . self::TABLE, $host_id ), ARRAY_A );
		$links = array();
		foreach ( is_array( $found ) ? $found : array() as $row ) {
			$links[ LocationKey::of( $host_id, (string) $row['source'], (string) $row['url_md5'], (int) $row['occurrence'] ) ] = array(
				'id'      => (int) $row['id'],
				'product' => (int) $row['assigned_product_id'] > 0 ? (int) $row['assigned_product_id'] : (int) $row['product_id'],
			);
		}

		return $links;
	}
}
