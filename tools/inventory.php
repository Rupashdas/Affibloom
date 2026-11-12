<?php
/**
 * Read-only inventory of how the site uses Lasso. Writes JSON fixtures.
 *
 * Run: bash tools/local-wp.sh eval-file tools/inventory.php
 *
 * @package Affibloom
 */

( static function () {
	global $wpdb;

	$out_dir = dirname( __DIR__ ) . '/tests/fixtures/inventory';
	wp_mkdir_p( $out_dir );

	$write = static function ( string $name, $data ) use ( $out_dir ): void {
		file_put_contents( // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- CLI tool.
			$out_dir . '/' . $name . '.json',
			wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n"
		);
	};

	// 1. Boxes: Lasso blocks and [lasso] shortcodes in published content.
	$boxes      = array();
	$links      = array();
	$attributes = array();
	$posts      = get_posts(
		array(
			'post_type'      => array( 'post', 'page' ),
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		)
	);

	$walk = static function ( array $blocks, WP_Post $post ) use ( &$walk, &$boxes, &$attributes ): void {
		foreach ( $blocks as $block ) {
			if ( 'affiliate-plugin/lasso' === $block['blockName'] ) {
				$inner = trim( (string) $block['innerHTML'] );
				$atts  = array();
				if ( preg_match( '/' . get_shortcode_regex( array( 'lasso' ) ) . '/', $inner, $m ) ) {
					$atts = shortcode_parse_atts( $m[3] );
					$atts = is_array( $atts ) ? $atts : array();
				}
				foreach ( array_keys( $atts ) as $key ) {
					$attributes[ $key ] = ( $attributes[ $key ] ?? 0 ) + 1;
				}
				$boxes[] = array(
					'post_id'     => $post->ID,
					'post_slug'   => $post->post_name,
					'source'      => 'block',
					'block_attrs' => $block['attrs'],
					'shortcode'   => $atts,
					'inner_html'  => $inner,
				);
			}
			if ( ! empty( $block['innerBlocks'] ) ) {
				$walk( $block['innerBlocks'], $post );
			}
		}
	};

	foreach ( $posts as $post ) {
		$walk( parse_blocks( $post->post_content ), $post );

		// Classic or shortcode-block content outside a Lasso block.
		$without_blocks = preg_replace( '#<!-- wp:affiliate-plugin/lasso.*?<!-- /wp:affiliate-plugin/lasso -->#s', '', $post->post_content );
		if ( preg_match_all( '/' . get_shortcode_regex( array( 'lasso' ) ) . '/', (string) $without_blocks, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $m ) {
				$atts = shortcode_parse_atts( $m[3] );
				$atts = is_array( $atts ) ? $atts : array();
				foreach ( array_keys( $atts ) as $key ) {
					$attributes[ $key ] = ( $attributes[ $key ] ?? 0 ) + 1;
				}
				$boxes[] = array(
					'post_id'   => $post->ID,
					'post_slug' => $post->post_name,
					'source'    => 'shortcode',
					'shortcode' => $atts,
				);
			}
		}

		// In-content links Lasso stamped with its record id.
		if ( preg_match_all( '/<a\s[^>]*data-lasso-lid="(\d+)"[^>]*>(.*?)<\/a>/is', $post->post_content, $found, PREG_SET_ORDER ) ) {
			foreach ( $found as $a ) {
				preg_match( '/href="([^"]*)"/i', $a[0], $href );
				$links[] = array(
					'post_id'  => $post->ID,
					'lasso_id' => (int) $a[1],
					'href'     => $href[1] ?? '',
					'text'     => wp_strip_all_tags( $a[2] ),
				);
			}
		}
	}

	// 2. Records, with everything a box is built from.
	$table    = static fn( string $name ): string => $wpdb->prefix . $name;
	$records  = get_posts(
		array(
			'post_type'      => 'lasso-urls',
			'post_status'    => array( 'publish', 'lasso_delete', 'draft', 'trash' ),
			'posts_per_page' => -1,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		)
	);
	$products = array();
	foreach ( $records as $record ) {
		$meta = array();
		foreach ( get_post_meta( $record->ID ) as $key => $values ) {
			$meta[ $key ] = maybe_unserialize( $values[0] );
		}
		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- read-only CLI inventory of Lasso tables.
		$details = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table( 'lasso_url_details' )} WHERE lasso_id = %d", $record->ID ), ARRAY_A );
		$amazon  = null;
		if ( $details && ! empty( $details['product_id'] ) ) {
			$amazon = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$table( 'lasso_amazon_products' )} WHERE amazon_id = %s AND country = %s",
					$details['product_id'],
					$details['country'] ?? 'us'
				),
				ARRAY_A
			);
		}
		$fields = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table( 'lasso_field_mapping' )} WHERE lasso_id = %d", $record->ID ), ARRAY_A );
		$issues = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table( 'lasso_url_issues' )} WHERE id = %d", $record->ID ), ARRAY_A );
		// phpcs:enable

		$products[] = array(
			'id'            => $record->ID,
			'title'         => $record->post_title,
			'slug'          => $record->post_name,
			'status'        => $record->post_status,
			'modified'      => $record->post_modified_gmt,
			'thumbnail_url' => get_the_post_thumbnail_url( $record->ID, 'large' ) ? get_the_post_thumbnail_url( $record->ID, 'large' ) : '',
			'meta'          => $meta,
			'url_details'   => $details,
			'amazon'        => $amazon,
			'fields'        => $fields,
			'issues'        => $issues,
		);
	}

	// 3. Settings, without anything secret. Lasso also keeps an Amazon access key ID, its cloud
	// site ID and an Intercom JWT (which carries the account owner's email) in this option.
	$settings = (array) get_option( 'lasso_settings', array() );
	foreach ( array_keys( $settings ) as $key ) {
		if ( preg_match( '/licen[cs]e|serial|secret|credential|token|password|access_key|jwt|^site_id$/i', (string) $key ) ) {
			unset( $settings[ $key ] );
		}
	}

	ksort( $attributes );
	$write( 'boxes', $boxes );
	$write( 'products', $products );
	$write( 'links', $links );
	$write( 'settings', $settings );
	$write( 'attributes', $attributes );

	$published = array_filter( $products, static fn( $p ) => 'publish' === $p['status'] );
	// A record is Amazon by Lasso's own product type; Lasso's cached product data is missing for some.
	$amazon  = array_filter( $published, static fn( $p ) => 'amazon' === ( $p['url_details']['product_type'] ?? '' ) );
	$cached  = array_filter( $amazon, static fn( $p ) => ! empty( $p['amazon'] ) );
	$no_link = array_filter( $published, static fn( $p ) => empty( $p['url_details'] ) );
	WP_CLI::log( sprintf( 'Boxes: %d in %d posts', count( $boxes ), count( array_unique( array_column( $boxes, 'post_id' ) ) ) ) );
	WP_CLI::log( sprintf( 'Records: %d published (%d Amazon, %d other, %d with no link), %d not published', count( $published ), count( $amazon ), count( $published ) - count( $amazon ) - count( $no_link ), count( $no_link ), count( $products ) - count( $published ) ) );
	WP_CLI::log( sprintf( 'Amazon records with cached product data: %d of %d', count( $cached ), count( $amazon ) ) );
	WP_CLI::log( sprintf( 'Links tied to a record: %d in %d posts', count( $links ), count( array_unique( array_column( $links, 'post_id' ) ) ) ) );
	WP_CLI::log( 'Shortcode attributes in use: ' . implode( ', ', array_map( static fn( $k, $v ) => "$k ($v)", array_keys( $attributes ), $attributes ) ) );
	WP_CLI::success( "Fixtures written to $out_dir" );
} )();
