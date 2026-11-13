<?php
/**
 * A product name read from a page's <title>.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Product;

/**
 * A product name read from a page's <title>: Lasso_Helper::get_page_title(), and the titles Lasso throws away.
 */
final class PageTitle {

	/** A product name is at most 200 characters (docs/spec/interface.md, section 4), dots included. */
	private const MAX_LENGTH = 200;

	/** Titles of challenge and error pages, which Lasso replaces with the site name (get_redirect_final_target). */
	private const PLACEHOLDER_PARTS = array( 'Please Wait...', 'Cloudflare', 'Access Denied', 'Just a moment...' );

	/** Whole titles Lasso throws away: Amazon's captcha page, and Lasso's own "no title yet" (DEFAULT_TITLE). */
	private const PLACEHOLDER_TITLES = array( 'Robot Check', 'Add a Link Title' );

	/**
	 * The first <title>, cleaned as Lasso does: whitespace collapsed, "–" as "-", trimmed, cut to 200 characters
	 * with "..." when longer. Two differences that keep more: entities are decoded and characters outside ASCII are
	 * kept (Lasso drops them, so "Men's" with a curly apostrophe became "Mens"); the name is escaped when printed.
	 * '' when there is no title.
	 *
	 * @param string $html The page.
	 */
	public static function from_html( string $html ): string {
		if ( ! preg_match( '/<title\b[^>]*>(.*?)<\/title>/is', $html, $match ) ) {
			return '';
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- Domain code does not call WordPress; a <title> holds no script or style for wp_strip_all_tags() to remove.
		$title = html_entity_decode( strip_tags( $match[1] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$title = trim( str_replace( '–', '-', (string) preg_replace( '/[\s\x{00A0}]+/u', ' ', $title ) ) );

		return mb_strlen( $title ) > self::MAX_LENGTH ? mb_substr( $title, 0, self::MAX_LENGTH - 3 ) . '...' : $title;
	}

	/**
	 * Whether a title is one Lasso throws away for the site name.
	 *
	 * @param string $title A title from from_html().
	 */
	public static function is_placeholder( string $title ): bool {
		foreach ( self::PLACEHOLDER_PARTS as $part ) {
			if ( str_contains( $title, $part ) ) {
				return true;
			}
		}
		return in_array( $title, self::PLACEHOLDER_TITLES, true );
	}
}
