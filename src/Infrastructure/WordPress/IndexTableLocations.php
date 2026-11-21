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
 * F21's index as the tables' Locations (F25 R11, D13; Task T1): the rows whose display is `table:<id>`, in posts that
 * still exist, whatever their status (Lasso counts drafts too).
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
}
