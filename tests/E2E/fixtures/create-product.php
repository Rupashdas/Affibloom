<?php
/**
 * Creates a test product in one WP-CLI call (deviation 111): `wp eval-file create-product.php '<json>'`.
 *
 * The JSON is { "title": string, "meta": object, "button_urls": string[] }, as utils.ts createProduct() builds it.
 * It does what `wp post create --meta_input=…` followed by one `wp post meta add` per button did, in one process:
 * wp_insert_post() with the meta, then one `_affibloom_button_url` row per button link (Task 13G). Prints the ID.
 *
 * @package Affibloom
 */

defined( 'WP_CLI' ) || exit;

$affibloom_input = json_decode( (string) ( $args[0] ?? '' ), true );
if ( ! is_array( $affibloom_input ) ) {
	WP_CLI::error( 'create-product.php: the argument is not a JSON object.' );
}

// wp_insert_post() and add_post_meta() strip one level of slashes, so their input is slashed first, as
// `wp post create` and `wp post meta add` do; otherwise a backslash in a title or a meta value would be lost.
$affibloom_id = wp_insert_post(
	wp_slash(
		array(
			'post_type'   => 'affibloom_product',
			'post_status' => 'publish',
			'post_title'  => (string) ( $affibloom_input['title'] ?? 'Test product' ),
			'meta_input'  => (array) ( $affibloom_input['meta'] ?? array() ),
		)
	),
	true
);
if ( is_wp_error( $affibloom_id ) ) {
	WP_CLI::error( $affibloom_id );
}

foreach ( (array) ( $affibloom_input['button_urls'] ?? array() ) as $affibloom_url ) {
	add_post_meta( $affibloom_id, '_affibloom_button_url', wp_slash( (string) $affibloom_url ) );
}

echo (int) $affibloom_id;
