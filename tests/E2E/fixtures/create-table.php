<?php
/**
 * Creates a test comparison table in one WP-CLI call: `wp eval-file create-table.php '<json>'` (F25, Task T1).
 *
 * The JSON is { "title": string, "style": "row"|"column", "theme": string, "products": int[] }, as utils.ts
 * createTable() builds it; the table is stored as F25 D1 says (the post's title, the rest in `_affibloom_table`).
 * Prints the ID.
 *
 * @package Affibloom
 */

defined( 'WP_CLI' ) || exit;

$affibloom_input = json_decode( (string) ( $args[0] ?? '' ), true );
if ( ! is_array( $affibloom_input ) ) {
	WP_CLI::error( 'create-table.php: the argument is not a JSON object.' );
}

$affibloom_id = wp_insert_post(
	wp_slash(
		array(
			'post_type'   => 'affibloom_table',
			'post_status' => 'publish',
			'post_title'  => (string) ( $affibloom_input['title'] ?? 'Table' ),
		)
	),
	true
);
if ( is_wp_error( $affibloom_id ) ) {
	WP_CLI::error( $affibloom_id->get_error_message() );
}
update_post_meta(
	$affibloom_id,
	'_affibloom_table',
	array(
		'style'           => (string) ( $affibloom_input['style'] ?? 'column' ),
		'theme'           => (string) ( $affibloom_input['theme'] ?? 'Cactus' ),
		'products'        => array_map(
			static fn( $id ): array => array(
				'id'    => (int) $id,
				'badge' => '',
			),
			(array) ( $affibloom_input['products'] ?? array() )
		),
		'groups'          => array(),
		'values'          => array(),
		'show_headers'    => true,
		'show_field_name' => false,
		'revision'        => 1,
	)
);

echo (int) $affibloom_id;
