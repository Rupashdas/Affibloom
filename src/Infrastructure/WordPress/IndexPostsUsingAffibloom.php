<?php
/**
 * The posts that still use Affibloom, read from the link index (Task 38).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Application\Uninstall\PostsUsingAffibloom;

/**
 * The posts that still use Affibloom, read from the link index (F21; Task 38): a post with a row tied or assigned to a
 * product, or a group or table display ("group:<id>", "table:<id>"). Lasso's own boxes are indexed too but tie to no
 * Affibloom product, so they do not count. Trashed posts have no rows (F21 D6).
 */
final class IndexPostsUsingAffibloom implements PostsUsingAffibloom {

	/**
	 * The newest posts, and how many there are in all.
	 *
	 * @param int $limit How many IDs to give.
	 * @return array{ids: list<int>, total: int}
	 */
	public function first( int $limit ): array {
		global $wpdb;

		$table = $wpdb->prefix . TableLinkLocationRepository::TABLE;
		$uses  = 'product_id > 0 OR assigned_product_id > 0 OR display_ref LIKE %s OR display_ref LIKE %s';
		$refs  = array( $wpdb->esc_like( 'group:' ) . '%', $wpdb->esc_like( 'table:' ) . '%' );
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Affibloom's own table, the Settings page only; $uses is the fixed rule above, its patterns placeholders.
		$ids   = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT host_id FROM %i WHERE {$uses} ORDER BY host_id DESC LIMIT %d", $table, ...array_merge( $refs, array( max( 1, $limit ) ) ) ) );
		$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT host_id) FROM %i WHERE {$uses}", $table, ...$refs ) );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

		return array(
			'ids'   => array_map( 'intval', $ids ),
			'total' => $total,
		);
	}
}
