<?php
/**
 * Marks the boxes in a text widget.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Front;

use Affibloom\Module;

/**
 * A text widget's `[affibloom …]` and `[lasso …]` get `origin="widget"`, so a single box there draws the phone layout
 * (F31 R16, A17), as Lasso's `widget_text` filter does; nothing while Elementor is active (Lasso's rule). Unlike Lasso,
 * only a shortcode name followed by a space or `]` is marked, so `[lasso-attr …]` stays whole (D11).
 */
final class WidgetShortcodes implements Module {

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_filter( 'widget_text', array( $this, 'filter' ), 1 );
	}

	/**
	 * The widget's text, its boxes marked unless Elementor is active.
	 *
	 * @param mixed $text The widget's text.
	 * @return mixed
	 */
	public function filter( mixed $text ): mixed {
		if ( ! is_string( $text ) || in_array( 'elementor/elementor.php', (array) get_option( 'active_plugins', array() ), true ) ) {
			return $text;
		}

		return self::marked( $text );
	}

	/**
	 * The text with `origin="widget"` after every `[affibloom` and `[lasso` that a space or `]` follows.
	 *
	 * @param string $text The text.
	 */
	public static function marked( string $text ): string {
		return (string) preg_replace( '/\[(affibloom|lasso)(?=[\s\]])/', '[$1 origin="widget"', $text );
	}
}
