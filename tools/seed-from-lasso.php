<?php
/**
 * Seeds the Affibloom library from the Lasso records on this copy, so the Dashboard can be looked at and tried
 * with the site's real products.
 *
 * THIS IS NOT THE MIGRATION. Tasks 25 and 26 build `wp affibloom migrate`, with its dry run, its field-by-field
 * mapping, its report and its checks; this tool only puts believable data on a development copy. It writes
 * nothing to Lasso: it reads the records and the `lasso_url_details` table and nothing else.
 *
 * Run from the plugin folder:
 *   bash tools/local-wp.sh eval-file tools/seed-from-lasso.php
 *
 * Running it twice is safe: a Lasso record that already has an Affibloom product (matched on
 * `_affibloom_lasso_id`) is skipped.
 *
 * @package Affibloom
 */

// No `declare( strict_types=1 )`: `wp eval-file` runs this through eval(), where a declare is a fatal error.

( static function (): void {
	global $wpdb;

	if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
		return;
	}

	if ( 'local' !== wp_get_environment_type() ) {
		WP_CLI::error( 'This tool only runs on a development copy.' );
	}

	/**
	 * A flag as Affibloom stores it, read from Lasso's own value (Lasso reads 1 === intval( … )).
	 */
	$flag = static function ( int $post_id, string $key, bool $on = false ): string {
		$raw = get_post_meta( $post_id, $key, true );

		if ( '' === $raw || null === $raw ) {
			return $on ? '1' : '0';
		}

		return 1 === (int) $raw ? '1' : '0';
	};

	/** A meta value as a string, never null. */
	$text = static function ( int $post_id, string $key ): string {
		return (string) get_post_meta( $post_id, $key, true );
	};

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- a one-off development tool reading Lasso's own table.
	$records = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT p.ID, p.post_title, p.post_date, p.post_modified, d.redirect_url, d.product_id,
				d.product_type, d.country, d.base_domain, d.search_text
			FROM {$wpdb->posts} AS p
			LEFT JOIN {$wpdb->prefix}lasso_url_details AS d ON d.lasso_id = p.ID
			WHERE p.post_type = %s AND p.post_status = %s
			ORDER BY p.post_modified DESC",
			'lasso-urls',
			'publish'
		)
	);

	$seeded  = 0;
	$skipped = 0;
	$empty   = array();

	foreach ( $records as $record ) {
		$lasso_id = (int) $record->ID;

		$already = get_posts(
			array(
				'post_type'      => 'affibloom_product',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_affibloom_lasso_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- a development tool.
				'meta_value'     => (string) $lasso_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- a development tool.
			)
		);
		if ( $already ) {
			++$skipped;
			continue;
		}

		// Lasso keeps the destination in `lasso_custom_redirect`; url_details.redirect_url mirrors it.
		$url = $text( $lasso_id, 'lasso_custom_redirect' );
		if ( '' === trim( $url ) ) {
			$url = (string) $record->redirect_url;
		}
		if ( '' === trim( $url ) ) {
			$empty[] = $lasso_id . ' ' . $record->post_title;
			continue;
		}

		$is_amazon = 'amazon' === (string) $record->product_type;
		$meta      = array(
			'_affibloom_lasso_id'        => (string) $lasso_id,
			'_affibloom_url'             => $url,
			// AffiliateUrl::key(): "&amp;" read as "&", trailing slashes dropped, lower case.
			'_affibloom_url_key'         => strtolower( rtrim( str_replace( '&amp;', '&', $url ), '/' ) ),
			'_affibloom_type'            => $is_amazon ? 'amazon' : 'other',
			'_affibloom_new_tab'         => $flag( $lasso_id, 'open_new_tab', true ),
			'_affibloom_nofollow'        => $flag( $lasso_id, 'enable_nofollow', true ),
			'_affibloom_sponsored'       => $flag( $lasso_id, 'enable_sponsored' ),
			'_affibloom_show_disclosure' => $flag( $lasso_id, 'show_disclosure', true ),
			'_affibloom_button_text'     => $text( $lasso_id, 'buy_btn_text' ),
			'_affibloom_image_url'       => $text( $lasso_id, 'lasso_custom_thumbnail' ),
			'_affibloom_badge'           => $text( $lasso_id, 'badge_text' ),
			'_affibloom_description'     => $text( $lasso_id, 'affiliate_desc' ),
			'_affibloom_disclosure'      => $text( $lasso_id, 'disclosure_text' ),
		);

		// Lasso's second button is the first of the product's buttons after the primary (F29, Task 14H). Its text is kept
		// without a link too, as Lasso keeps it (123 records here); a button with neither is not written.
		$second_url  = trim( $text( $lasso_id, 'second_btn_url' ) );
		$second_text = $text( $lasso_id, 'second_btn_text' );
		if ( '' !== $second_url || '' !== trim( $second_text ) ) {
			$meta['_affibloom_buttons'] = array(
				array(
					'url'      => $second_url,
					'text'     => $second_text,
					'new_tab'  => '1' === $flag( $lasso_id, 'open_new_tab2', true ),
					'nofollow' => '1' === $flag( $lasso_id, 'enable_nofollow2', true ),
				),
			);
		}

		if ( $is_amazon ) {
			$meta['_affibloom_asin']           = (string) $record->product_id;
			$meta['_affibloom_amazon_country'] = '' !== (string) $record->country ? (string) $record->country : 'us';
		}

		if ( '' !== (string) $record->search_text ) {
			// A search page's key, as AmazonUrl::search_key() builds it: the host, a pipe, the keywords.
			$meta['_affibloom_search_key'] = $record->base_domain . '|' . $record->search_text;
		}

		$id = wp_insert_post(
			array(
				'post_type'   => 'affibloom_product',
				'post_status' => 'publish',
				'post_title'  => $record->post_title,
				'post_date'   => $record->post_date,
				'meta_input'  => $meta,
			),
			true
		);

		if ( is_wp_error( $id ) ) {
			WP_CLI::warning( $lasso_id . ': ' . $id->get_error_message() );
			continue;
		}

		// wp_insert_post() stamps post_modified with "now"; the Dashboard orders by it, so put Lasso's back.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- post_modified cannot be set through wp_insert_post().
		$wpdb->update(
			$wpdb->posts,
			array(
				'post_modified'     => (string) $record->post_modified,
				'post_modified_gmt' => get_gmt_from_date( (string) $record->post_modified ),
			),
			array( 'ID' => (int) $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
		clean_post_cache( (int) $id );

		++$seeded;
	}

	WP_CLI::log( 'Lasso records read:       ' . count( $records ) );
	WP_CLI::log( 'Seeded:                   ' . $seeded );
	WP_CLI::log( 'Skipped (already seeded): ' . $skipped );
	WP_CLI::log( 'Skipped (no link at all): ' . count( $empty ) );
	foreach ( $empty as $line ) {
		WP_CLI::log( '  ' . $line );
	}
	WP_CLI::success( 'Done. A development seed, not the migration (Tasks 25-26).' );
} )();
