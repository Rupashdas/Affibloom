<?php
/**
 * The posts the link index covers.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Application\LinkIndex\Hosts;

/**
 * F21 D6 and D7: posts, pages and reusable blocks, published, scheduled, draft, pending or private (decision 6; Lasso
 * scans published and draft only). Task 35D adds the Custom Post Types setting.
 */
final class HostQuery implements Hosts {

	/** The post types always indexed. */
	public const TYPES = array( 'post', 'page', 'wp_block' );

	/** The statuses indexed. */
	public const STATUSES = array( 'publish', 'future', 'draft', 'pending', 'private' );

	/**
	 * Every indexed post's ID, in ID order.
	 *
	 * @return list<int>
	 */
	public function ids(): array {
		global $wpdb;

		$types    = implode( ', ', array_fill( 0, count( self::TYPES ), '%s' ) );
		$statuses = implode( ', ', array_fill( 0, count( self::STATUSES ), '%s' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- one list of IDs per rebuild; the placeholders are built above.
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type IN ( {$types} ) AND post_status IN ( {$statuses} ) ORDER BY ID", ...self::TYPES, ...self::STATUSES ) );

		return array_map( 'intval', $ids );
	}
}
