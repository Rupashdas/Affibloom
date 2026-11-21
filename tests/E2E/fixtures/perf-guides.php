<?php
/**
 * Task 37B: the pages the performance review measures, made from the real guide best-volleyball-shoes-for-hitters
 * (ten Lasso boxes). Each is kept under a fixed slug and rewritten on every run, so runs do not pile up posts:
 *   affibloom-perf-blocks      the guide with each Lasso box as Affibloom's block (the Lasso ID mapped to the product
 *                              Affibloom imported, `_affibloom_lasso_id`): the "converted by hand" copy of the plan;
 *   affibloom-perf-shortcodes  the same with `[affibloom id="…"]` shortcodes;
 *   affibloom-perf-plain       the guide with its boxes taken out: the same page without boxes;
 *   affibloom-perf-table       a comparison table (F25);
 *   affibloom-perf-group       a group as a grid (F23, F30).
 * Prints JSON: { guide: <the guide's path>, <slug>: <path>, …, boxes: <number of boxes converted> }.
 *
 * Run: bash tools/local-wp.sh eval-file tests/E2E/fixtures/perf-guides.php
 *
 * @package Affibloom
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- a WP-CLI script, not plugin code.

$guide = get_page_by_path( 'best-volleyball-shoes-for-hitters', OBJECT, 'post' );
if ( ! $guide instanceof WP_Post ) {
	fwrite( STDERR, "The guide best-volleyball-shoes-for-hitters is missing.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- WP-CLI output.
	exit( 1 );
}

/** The Affibloom product imported from a Lasso ID, or 0. */
$product_for = static function ( string $lasso_id ): int {
	$ids = get_posts(
		array(
			'post_type'   => 'affibloom_product',
			'post_status' => 'publish',
			'meta_key'    => '_affibloom_lasso_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- a test script.
			'meta_value'  => $lasso_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- a test script.
			'fields'      => 'ids',
			'numberposts' => 1,
		)
	);
	return (int) ( $ids[0] ?? 0 );
};

$boxes = 0;
/**
 * The guide with each Lasso block replaced by what $make returns for its product.
 *
 * @param callable(int): string $make The markup for one product.
 */
$convert = static function ( callable $make ) use ( $guide, $product_for, &$boxes ): string {
	$boxes = 0;
	return (string) preg_replace_callback(
		'#<!-- wp:affiliate-plugin/lasso (\{.*?\}) (?:/-->|-->.*?<!-- /wp:affiliate-plugin/lasso -->)#s',
		static function ( array $found ) use ( $make, $product_for, &$boxes ): string {
			$attrs = json_decode( $found[1], true );
			preg_match( '/\bid="(\d+)"/', (string) ( $attrs['short_code'] ?? '' ), $id );
			// A box whose product is gone from the dummy data (trashed by a test) gets a stand-in, so the page keeps ten.
			$product = $product_for( $id[1] ?? '' );
			if ( 0 === $product ) {
				$product = (int) ( get_posts(
					array(
						'post_type'   => 'affibloom_product',
						'post_status' => 'publish',
						'orderby'     => 'ID',
						'order'       => 'ASC',
						'offset'      => $boxes,
						'fields'      => 'ids',
						'numberposts' => 1,
					)
				)[0] ?? 0 );
			}
			if ( 0 === $product ) {
				return '';
			}
			++$boxes;
			return $make( $product );
		},
		$guide->post_content
	);
};

$perf_pages = array(
	'affibloom-perf-blocks'     => $convert( static fn( int $id ): string => '<!-- wp:affibloom/product-box {"productId":' . $id . '} /-->' ),
	'affibloom-perf-shortcodes' => $convert( static fn( int $id ): string => "<!-- wp:shortcode -->\n[affibloom id=\"{$id}\"]\n<!-- /wp:shortcode -->" ),
	'affibloom-perf-plain'      => $convert( static fn(): string => '' ),
	'affibloom-perf-table'      => "<!-- wp:shortcode -->\n[affibloom type=\"table\" id=\"20787\"]\n<!-- /wp:shortcode -->",
	'affibloom-perf-group'      => "<!-- wp:shortcode -->\n[affibloom type=\"grid\" group=\"pair-grid\"]\n<!-- /wp:shortcode -->",
);
// $boxes is the count of the last conversion (the plain page), the same for each copy of the guide.
$out = array(
	'guide' => wp_make_link_relative( (string) get_permalink( $guide ) ),
	'boxes' => $boxes,
);
foreach ( $perf_pages as $slug => $content ) {
	$existing     = get_page_by_path( $slug, OBJECT, 'post' );
	$made         = wp_insert_post(
		array(
			'ID'           => $existing instanceof WP_Post ? $existing->ID : 0,
			'post_type'    => 'post',
			'post_status'  => 'publish',
			'post_name'    => $slug,
			'post_title'   => 'Performance: ' . $slug,
			'post_content' => wp_slash( $content ),
		),
		true
	);
	$out[ $slug ] = is_wp_error( $made ) ? '' : '/?p=' . $made;
}

echo wp_json_encode( $out );
