<?php
/**
 * Puts the two button icon links right on the Local copy (F28 D4). The site's Additional CSS (the snapshot, as
 * production today) links full-review-icon and youtube-icon to uploads/2023/04/, where no file ever was, so every reset
 * brought the broken pictures back. This repoints Full review to the file that exists (2023/05) and YouTube to the
 * white play glyph kept beside this script, copied into uploads and registered in the media library when missing.
 * It changes nothing once the links are right, so it stops mattering when production's CSS is fixed.
 *
 * Run: bash tools/local-wp.sh eval-file tools/local-icon-links.php (reset-local.sh runs it after each reset).
 *
 * @package Affibloom
 */

( static function () {
	$stylesheet = get_stylesheet();
	$post       = wp_get_custom_css_post( $stylesheet );
	if ( ! $post instanceof WP_Post ) {
		WP_CLI::log( 'Icon links: the theme has no Additional CSS; nothing to do.' );
		return;
	}

	// The YouTube glyph: the file in uploads, then its media library item.
	$relative = '2026/11/youtube-icon.svg';
	$uploads  = wp_get_upload_dir();
	$file     = $uploads['basedir'] . '/' . $relative;
	if ( ! file_exists( $file ) ) {
		wp_mkdir_p( dirname( $file ) );
		copy( __DIR__ . '/youtube-icon.svg', $file );
	}
	if ( 0 === attachment_url_to_postid( $uploads['baseurl'] . '/' . $relative ) ) {
		wp_insert_attachment(
			array(
				'post_title'     => 'youtube-icon',
				'post_mime_type' => 'image/svg+xml',
				'post_status'    => 'inherit',
				'guid'           => $uploads['baseurl'] . '/' . $relative,
			),
			$file
		);
	}

	$links = array(
		'/wp-content/uploads/2023/04/full-review-icon.svg' => '/wp-content/uploads/2023/05/full-review-icon.svg',
		'/wp-content/uploads/2023/04/youtube-icon.svg'     => '/wp-content/uploads/' . $relative,
	);
	$count = 0;
	$css   = str_replace( array_keys( $links ), array_values( $links ), $post->post_content, $count );
	if ( 0 === $count ) {
		WP_CLI::log( 'Icon links: already right.' );
		return;
	}

	$result = wp_update_custom_css_post(
		$css,
		array(
			'stylesheet'   => $stylesheet,
			'preprocessed' => $post->post_content_filtered,
		)
	);
	if ( is_wp_error( $result ) ) {
		WP_CLI::error( 'Icon links: ' . $result->get_error_message() );
	}

	// A stored icon set imported the old links: repoint those two entries in place, keeping any icon added in Affibloom.
	$set = get_option( 'affibloom_icons' );
	if ( is_array( $set ) ) {
		foreach ( $set as $key => $icon ) {
			if ( ! empty( $icon['imported'] ) && isset( $icon['url'], $links[ $icon['url'] ] ) ) {
				$set[ $key ]['url']        = $links[ $icon['url'] ];
				$set[ $key ]['attachment'] = attachment_url_to_postid( home_url( $links[ $icon['url'] ] ) );
			}
		}
		update_option( 'affibloom_icons', $set );
	}

	WP_CLI::success( "Icon links: {$count} repointed." );
} )();
