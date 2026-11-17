<?php
/**
 * Whether the page being served shows a product box.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

/**
 * Smart CSS Loading's question (F5 R46, D12; Lasso's Helper::page_needs_lasso_frontend_css): any view that is not a
 * single post or page may show boxes (archives, the home page), so it needs the CSS; a single post or page needs it
 * when its content or its Elementor data holds a box: Affibloom's block or shortcode (Lasso's own markers are not read:
 * the separate migrator converts them, deviation 146). The usage index (Tasks 35A-35C) adds the boxes found in
 * link locations when it exists. Boxes outside the content (widgets, theme templates) are caught by the late enqueue.
 */
final class BoxDetector {

	/**
	 * Text that means a box: Affibloom's block and shortcode (R46).
	 *
	 * @var list<string>
	 */
	private const MARKERS = array(
		'wp:affibloom/',
		'[affibloom',
	);

	/**
	 * Whether the current request's page needs the box's CSS.
	 */
	public function page_needs_css(): bool {
		if ( ! is_singular() ) {
			return true;
		}
		$id = get_queried_object_id();
		if ( $id <= 0 ) {
			return true;
		}

		$elementor = get_post_meta( $id, '_elementor_data', true );

		return self::holds_box( (string) get_post_field( 'post_content', $id ) )
			|| ( is_string( $elementor ) && self::holds_box( $elementor ) );
	}

	/**
	 * Whether text holds one of the markers.
	 *
	 * @param string $text Post content or Elementor data.
	 */
	public static function holds_box( string $text ): bool {
		foreach ( self::MARKERS as $marker ) {
			if ( str_contains( $text, $marker ) ) {
				return true;
			}
		}

		return false;
	}
}
