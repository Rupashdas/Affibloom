<?php
/**
 * Where comparison tables are used, from the link index.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Domain\Table\TableLocations;

/**
 * F21's index as the tables' Locations (F25 R11, R40, D13; Tasks T1, T10): the rows whose display is `table:<id>`, in
 * posts that still exist, whatever their status (Lasso counts drafts too); the count, and the posts a page at a time.
 */
final class IndexTableLocations implements TableLocations {

	/**
	 * How many places show each table.
	 *
	 * @param list<int> $table_ids The tables.
	 * @return array<int, int> By table; a table shown nowhere is left out.
	 */
	public function counts( array $table_ids ): array {
		global $wpdb;

		$refs = array_map( static fn( int $id ): string => 'table:' . $id, array_values( array_filter( array_map( 'intval', $table_ids ) ) ) );
		if ( array() === $refs ) {
			return array();
		}
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Affibloom's own table, one page of tables; the list holds only placeholders.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT l.display_ref, COUNT(*) AS places FROM %i AS l JOIN %i AS h ON h.ID = l.host_id
				WHERE l.display_ref IN (' . implode( ',', array_fill( 0, count( $refs ), '%s' ) ) . ') GROUP BY l.display_ref',
				$wpdb->prefix . TableLinkLocationRepository::TABLE,
				$wpdb->posts,
				...$refs
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

		$counts = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$counts[ (int) substr( (string) $row['display_ref'], 6 ) ] = (int) $row['places'];
		}

		return $counts;
	}

	/**
	 * One page of the posts that show a table (R40; Task T10): each post once, newest first.
	 *
	 * @param int $table    The table.
	 * @param int $page     The page, from 1.
	 * @param int $per_page Posts a page.
	 * @return array{items: list<array{id: int, title: string}>, total: int}
	 */
	public function page( int $table, int $page, int $per_page ): array {
		global $wpdb;

		$ref = 'table:' . $table;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Affibloom's own table, one page of posts.
		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(DISTINCT l.host_id) FROM %i AS l JOIN %i AS h ON h.ID = l.host_id WHERE l.display_ref = %s',
				$wpdb->prefix . TableLinkLocationRepository::TABLE,
				$wpdb->posts,
				$ref
			)
		);
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT DISTINCT h.ID AS id, h.post_title AS title FROM %i AS l JOIN %i AS h ON h.ID = l.host_id
				WHERE l.display_ref = %s ORDER BY h.ID DESC LIMIT %d OFFSET %d',
				$wpdb->prefix . TableLinkLocationRepository::TABLE,
				$wpdb->posts,
				$ref,
				$per_page,
				( max( 1, $page ) - 1 ) * $per_page
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return array(
			'items' => array_map(
				static fn( array $row ): array => array(
					'id'    => (int) $row['id'],
					'title' => (string) $row['title'],
				),
				is_array( $rows ) ? array_values( $rows ) : array()
			),
			'total' => $total,
		);
	}
}
