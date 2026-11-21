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
use Affibloom\Domain\LinkIndex\LocationHost;
use Affibloom\Domain\LinkIndex\LocationKey;
use Affibloom\Domain\LinkIndex\LocationPage;
use Affibloom\Domain\LinkIndex\ProductLocation;
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
	 * "This product is used somewhere" as SQL (F12 L1, D20; the Placements filter, Task 36D): a row tied or assigned to
	 * it, or a group display of one of its groups. Written for the indexes: product_id and assigned_product_id each.
	 *
	 * @param string $product The SQL expression of the product's ID (a column of the outer query).
	 */
	public static function used_sql( string $product ): string {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $product is a column of the caller's query, never input.
		$sql = $wpdb->prepare(
			"( EXISTS ( SELECT 1 FROM %i AS afl_t WHERE afl_t.assigned_product_id = {$product} OR ( afl_t.assigned_product_id = 0 AND afl_t.product_id = {$product} ) )
				OR EXISTS ( SELECT 1 FROM %i AS afl_g JOIN %i AS afl_tt ON afl_tt.taxonomy = %s AND afl_g.display_ref = CONCAT('group:', afl_tt.term_id)
					JOIN %i AS afl_tr ON afl_tr.term_taxonomy_id = afl_tt.term_taxonomy_id WHERE afl_tr.object_id = {$product} ) )",
			$table,
			$table,
			$wpdb->term_taxonomy,
			GroupTaxonomy::NAME,
			$wpdb->term_relationships
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $sql;
	}

	/**
	 * A product's Locations count as an SQL value (F12 L1; the Locations sort, Task 13K): the rows tied or assigned to
	 * it plus the group displays of its groups, as counts_for() counts them.
	 *
	 * @param string $product The SQL expression of the product's ID (a column of the outer query).
	 */
	public static function count_sql( string $product ): string {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $product is a column of the caller's query, never input.
		$sql = $wpdb->prepare(
			"( ( SELECT COUNT(*) FROM %i AS afc_t WHERE afc_t.assigned_product_id = {$product} OR ( afc_t.assigned_product_id = 0 AND afc_t.product_id = {$product} ) )
				+ ( SELECT COUNT(*) FROM %i AS afc_g JOIN %i AS afc_tt ON afc_tt.taxonomy = %s AND afc_g.display_ref = CONCAT('group:', afc_tt.term_id)
					JOIN %i AS afc_tr ON afc_tr.term_taxonomy_id = afc_tt.term_taxonomy_id
					WHERE afc_tr.object_id = {$product} AND afc_tr.object_id <> IF(afc_g.assigned_product_id > 0, afc_g.assigned_product_id, afc_g.product_id) ) )",
			$table,
			$table,
			$wpdb->term_taxonomy,
			GroupTaxonomy::NAME,
			$wpdb->term_relationships
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $sql;
	}

	/**
	 * How many places each product is used in (F12 L1, D20; the Dashboard's column, Task 36A; the export's
	 * location_count, Task 31D): its rows, an editor's assignment first, plus the group displays (`display_ref`
	 * "group:<term id>") whose group holds it, read from the group's members now (F21 D2). A display tied to the product
	 * itself is not counted twice. Trashed hosts have no rows (F21 D6).
	 *
	 * @param list<int> $product_ids The products.
	 * @return array<int, int> By product ID; a product used nowhere is left out.
	 */
	public function counts_for( array $product_ids ): array {
		global $wpdb;

		$ids = implode( ',', array_map( 'intval', $product_ids ) );
		if ( '' === $ids ) {
			return array();
		}
		$table = $wpdb->prefix . self::TABLE;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Affibloom's own table, one query per list page; the IDs are integers.
		$found = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT used.product, COUNT(*) AS used FROM (
					SELECT IF(assigned_product_id > 0, assigned_product_id, product_id) AS product FROM %i
					WHERE assigned_product_id IN ({$ids}) OR ( assigned_product_id = 0 AND product_id IN ({$ids}) )
					UNION ALL
					SELECT tr.object_id AS product FROM %i AS l
					JOIN %i AS tt ON tt.taxonomy = %s AND l.display_ref = CONCAT('group:', tt.term_id)
					JOIN %i AS tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
					WHERE tr.object_id IN ({$ids}) AND tr.object_id <> IF(l.assigned_product_id > 0, l.assigned_product_id, l.product_id)
				) AS used GROUP BY used.product",
				$table,
				$table,
				$wpdb->term_taxonomy,
				GroupTaxonomy::NAME,
				$wpdb->term_relationships
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$counts = array();
		foreach ( is_array( $found ) ? $found : array() as $row ) {
			$counts[ (int) $row['product'] ] = (int) $row['used'];
		}

		return $counts;
	}

	/**
	 * One page of the posts that use a product (Task 36B): the rows L1 counts (counts_for()'s), found by the product's
	 * own rows and by the group displays of its groups; three queries: the hosts' total, the page's hosts, their rows.
	 *
	 * @param int    $product_id The product.
	 * @param string $search     The search, '' for none: host title, link text or URL (D23).
	 * @param string $order      'asc' or 'desc', by host title.
	 * @param int    $page       The page, from 1.
	 * @param int    $per_page   Hosts per page.
	 */
	public function product_locations( int $product_id, string $search, string $order, int $page, int $per_page ): LocationPage {
		global $wpdb;

		$groups = wp_get_object_terms( $product_id, GroupTaxonomy::NAME, array( 'fields' => 'ids' ) );
		$refs   = is_array( $groups ) ? array_map( static fn( $id ): string => 'group:' . (int) $id, $groups ) : array();
		$where  = 'IF(l.assigned_product_id > 0, l.assigned_product_id, l.product_id) = %d';
		$args   = array( $product_id );
		if ( array() !== $refs ) {
			$where = "( {$where} OR l.display_ref IN (" . implode( ',', array_fill( 0, count( $refs ), '%s' ) ) . ') )';
			$args  = array_merge( $args, $refs );
		}
		if ( '' !== $search ) {
			$like   = '%' . $wpdb->esc_like( $search ) . '%';
			$where .= ' AND ( p.post_title LIKE %s OR l.anchor_text LIKE %s OR l.url LIKE %s )';
			$args   = array_merge( $args, array( $like, $like, $like ) );
		}
		$from      = array( $wpdb->prefix . self::TABLE, $wpdb->posts );
		$direction = 'asc' === $order ? 'ASC' : 'DESC';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Affibloom's own table, read for one admin screen; $where holds only placeholders and $direction is ASC or DESC.
		$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT l.host_id) FROM %i AS l JOIN %i AS p ON p.ID = l.host_id WHERE {$where}", ...$from, ...$args ) );
		$shell = new LocationPage( array(), $total, $page, $per_page );
		$hosts = 0 === $total ? array() : $wpdb->get_results(
			$wpdb->prepare(
				"SELECT l.host_id, p.post_title, p.post_status, MAX(l.host_url) AS host_url FROM %i AS l JOIN %i AS p ON p.ID = l.host_id
				WHERE {$where} GROUP BY l.host_id, p.post_title, p.post_status ORDER BY p.post_title {$direction}, l.host_id {$direction} LIMIT %d OFFSET %d",
				...array_merge( $from, $args, array( $per_page, $shell->offset() ) )
			),
			ARRAY_A
		);
		$hosts = is_array( $hosts ) ? $hosts : array();
		$ids   = implode( ',', array_map( static fn( array $host ): int => (int) $host['host_id'], $hosts ) );
		$rows  = '' === $ids ? array() : $wpdb->get_results(
			$wpdb->prepare( "SELECT l.id, l.host_id, l.display_type, l.url, l.anchor_text, l.display_ref FROM %i AS l JOIN %i AS p ON p.ID = l.host_id WHERE {$where} AND l.host_id IN ({$ids}) ORDER BY l.id", ...$from, ...$args ),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

		$places = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$places[ (int) $row['host_id'] ][] = new ProductLocation(
				(int) $row['id'],
				(string) $row['display_type'],
				(string) $row['url'],
				null === $row['anchor_text'] ? null : (string) $row['anchor_text'],
				null === $row['display_ref'] ? null : (string) $row['display_ref']
			);
		}
		$items = array_map(
			static fn( array $host ): LocationHost => new LocationHost( (int) $host['host_id'], (string) $host['post_title'], (string) $host['post_status'], (string) $host['host_url'], $places[ (int) $host['host_id'] ] ?? array() ),
			$hosts
		);

		return new LocationPage( $items, $total, $shell->current(), $per_page );
	}

	/**
	 * Dismisses a row, or brings it back (F22 R45, D11; Task O7).
	 *
	 * @param int  $id      The row's ID.
	 * @param bool $ignored True to dismiss, false to undo.
	 * @return bool False when the row is gone.
	 */
	public function set_ignored( int $id, bool $ignored ): bool {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Affibloom's own table, one row.
		if ( null === $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM %i WHERE id = %d', $table, $id ) ) ) {
			return false;
		}
		$wpdb->update( $table, array( 'is_ignored' => $ignored ? 1 : 0 ), array( 'id' => $id ), array( '%d' ), array( '%d' ) );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return true;
	}

	/**
	 * Ties a row to a product at render time, or clears that (F22 N1-A; Task O10). A rescan copies an assignment into
	 * `product_id` (LinkLocation::keeping()), so a tie that came from the assignment moves with it, and goes with it.
	 *
	 * @param int $id         The row's ID.
	 * @param int $product_id The product; 0 clears the assignment.
	 * @return bool False when the row is gone.
	 */
	public function set_assigned( int $id, int $product_id ): bool {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Affibloom's own table, one row.
		if ( null === $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM %i WHERE id = %d', $table, $id ) ) ) {
			return false;
		}
		$product_id = max( 0, $product_id );
		// product_id first: MySQL sets the columns in order, so the test reads the old assignment.
		$wpdb->query( $wpdb->prepare( 'UPDATE %i SET product_id = IF( assigned_product_id > 0 AND product_id = assigned_product_id, %d, product_id ), assigned_product_id = %d WHERE id = %d', $table, $product_id, $product_id, $id ) );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return true;
	}

	/**
	 * One row by its ID.
	 *
	 * @param int $id The row's ID.
	 */
	public function find( int $id ): ?LinkLocation {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Affibloom's own table, one row for a preview.
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $wpdb->prefix . self::TABLE, $id ), ARRAY_A );
		if ( ! is_array( $row ) ) {
			return null;
		}
		$text = static fn( string $key ): ?string => null === $row[ $key ] ? null : (string) $row[ $key ];

		return new LinkLocation(
			host_id: (int) $row['host_id'],
			host_url: (string) $row['host_url'],
			source: (string) $row['source'],
			occurrence: (int) $row['occurrence'],
			product_id: (int) $row['product_id'],
			link_type: (string) $row['link_type'],
			display_type: (string) $row['display_type'],
			url: (string) $row['url'],
			url_md5: (string) $row['url_md5'],
			anchor_text: $text( 'anchor_text' ),
			domain: $text( 'domain' ),
			asin: $text( 'asin' ),
			tracking_id: $text( 'tracking_id' ),
			country: (string) $row['country'],
			no_follow: '1' === (string) $row['no_follow'],
			new_window: '1' === (string) $row['new_window'],
			affiliate_platform: $text( 'affiliate_platform' ),
			detected_at: (string) $row['detected_at'],
			display_ref: $text( 'display_ref' ),
			original_url: (string) $row['original_url'],
			is_ignored: '1' === (string) $row['is_ignored'],
			assigned_product_id: (int) $row['assigned_product_id'],
		);
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
	 * A host's links by their key, with the row's ID and its product (an editor's assignment first; 0 when none;
	 * Tasks 35H, 35E).
	 *
	 * @param int $host_id The post.
	 * @return array<string, array{id: int, product: int}>
	 */
	public function host_links( int $host_id ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Affibloom's own table, once per printed post (RenderedLinks keeps it for the request).
		$found = $wpdb->get_results( $wpdb->prepare( 'SELECT id, source, url_md5, occurrence, product_id, assigned_product_id FROM %i WHERE host_id = %d', $wpdb->prefix . self::TABLE, $host_id ), ARRAY_A );
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
