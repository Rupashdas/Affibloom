<?php
/**
 * The fingerprint of the site's content (Task 29E): an md5 of every post's content (revisions, auto-drafts and the
 * Customizer's aside), of each post's Elementor data, of the text, HTML and block widgets, and of the active theme's
 * Additional CSS. Equal before a migration and after its rollback proves the content came back byte for byte.
 *
 * Run: bash tools/local-wp.sh eval-file <path>/tools/content-fingerprint.php > before.json
 *
 * It only reads; it prints hashes, never content.
 *
 * @package Affibloom
 */

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- a read-only check run by hand.

defined( 'ABSPATH' ) || exit;

global $wpdb;

$affibloom_hashes = array();
foreach ( $wpdb->get_results( "SELECT ID, post_content FROM {$wpdb->posts} WHERE post_type NOT IN ('revision', 'customize_changeset', 'custom_css') AND post_status <> 'auto-draft' ORDER BY ID" ) as $affibloom_row ) {
	$affibloom_hashes[ 'post ' . $affibloom_row->ID ] = md5( (string) $affibloom_row->post_content );
}
foreach ( $wpdb->get_results( "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_elementor_data' ORDER BY post_id" ) as $affibloom_row ) {
	$affibloom_hashes[ 'elementor ' . $affibloom_row->post_id ] = md5( (string) $affibloom_row->meta_value );
}
foreach ( array( 'widget_text', 'widget_custom_html', 'widget_block' ) as $affibloom_option ) {
	$affibloom_hashes[ $affibloom_option ] = md5( (string) wp_json_encode( get_option( $affibloom_option ) ) );
}
$affibloom_css                      = wp_get_custom_css_post();
$affibloom_hashes['additional css'] = md5( $affibloom_css instanceof WP_Post ? (string) $affibloom_css->post_content : '' );

echo wp_json_encode( $affibloom_hashes, JSON_PRETTY_PRINT ), "\n";
