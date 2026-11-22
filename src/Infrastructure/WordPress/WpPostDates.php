<?php
/**
 * A post's created and changed dates, written as given.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

/**
 * A post's created and changed dates, written as given (Task 26A: an imported product keeps its record's dates, so the
 * Dashboard lists it in the record's order). wp_insert_post() and wp_update_post() stamp post_modified with "now"
 * whatever they are given, so the row is written directly, then the post's cache cleared.
 */
final class WpPostDates {

	/**
	 * Writes the dates.
	 *
	 * @param int    $id       The post.
	 * @param string $created  "Y-m-d H:i:s" in the site's time.
	 * @param string $modified As $created.
	 */
	public function set( int $id, string $created, string $modified ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- post_modified cannot be set through wp_update_post(); the cache is cleared below.
		$wpdb->update(
			$wpdb->posts,
			array(
				'post_date'         => $created,
				'post_date_gmt'     => get_gmt_from_date( $created ),
				'post_modified'     => $modified,
				'post_modified_gmt' => get_gmt_from_date( $modified ),
			),
			array( 'ID' => $id ),
			array( '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);
		clean_post_cache( $id );
	}
}
