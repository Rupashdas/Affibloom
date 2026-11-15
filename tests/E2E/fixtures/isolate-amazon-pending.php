<?php
/**
 * Leaves only the given products pending bulk Amazon sync (C1 AC7, AC14): `wp eval-file isolate-amazon-pending.php
 * '<json list of IDs>'`. Every other pending product with an ASIN gets a stored row for its ASIN and store, so it no
 * longer counts; one without an ASIN becomes a draft. The test run's restore (run-state.php) deletes the rows and puts
 * the statuses back.
 *
 * @package Affibloom
 */

defined( 'WP_CLI' ) || exit;

use Affibloom\Infrastructure\WordPress\PostMetaPendingAmazonSync;

$affibloom_keep = json_decode( (string) ( $args[0] ?? '' ), true );
if ( ! is_array( $affibloom_keep ) ) {
	WP_CLI::error( 'isolate-amazon-pending.php: the argument is not a JSON list of IDs.' );
}
$affibloom_keep = array_map( 'intval', $affibloom_keep );

global $wpdb;
foreach ( ( new PostMetaPendingAmazonSync() )->ids() as $affibloom_id ) {
	if ( in_array( $affibloom_id, $affibloom_keep, true ) ) {
		continue;
	}
	$affibloom_asin = (string) get_post_meta( $affibloom_id, '_affibloom_asin', true );
	if ( '' === $affibloom_asin ) {
		$wpdb->update( $wpdb->posts, array( 'post_status' => 'draft' ), array( 'ID' => $affibloom_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		clean_post_cache( $affibloom_id );
		continue;
	}
	$affibloom_country = (string) get_post_meta( $affibloom_id, '_affibloom_amazon_country', true );
	$wpdb->replace( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->prefix . 'affibloom_amazon_products',
		array(
			'asin'         => $affibloom_asin,
			'country'      => '' === $affibloom_country ? 'us' : $affibloom_country,
			'name'         => 'Isolated by the test',
			'base_url'     => '',
			'monetized_url' => '',
			'image'        => '',
			'features'     => '[]',
			'source'       => 'creators',
			'last_updated' => gmdate( 'Y-m-d H:i:s' ),
		)
	);
}
