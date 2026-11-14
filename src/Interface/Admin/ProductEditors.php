<?php
/**
 * The product page's two rich text editors and their Lasso toolbars (Task 14B).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

/**
 * The product page's two rich text editors (WordPress's TinyMCE through wp_editor()) and their toolbars, Lasso's Quill
 * ones (url-details.php 562-577 and 363-378). Another plugin can rewrite every editor's settings: Advanced Editor Tools,
 * active on this site, adds its menu bar and toolbars through tiny_mce_before_init. These two editors take their
 * toolbars last, so they keep Lasso's whatever else is installed.
 */
final class ProductEditors {

	/** Editor id => Lasso's toolbar. */
	public const TOOLBARS = array(
		'affibloom_description' => 'bold,italic,underline,strikethrough,link,bullist,forecolor,backcolor,removeformat',
		'affibloom_price'       => 'bold,italic,underline,strikethrough,forecolor',
	);

	/**
	 * Applies the toolbars to the editors printed after this call.
	 */
	public static function apply(): void {
		add_filter( 'tiny_mce_before_init', array( self::class, 'settings' ), PHP_INT_MAX, 2 );
	}

	/**
	 * One editor's TinyMCE settings: the product page's editors get Lasso's toolbar, no other row and no menu bar; every
	 * other editor is left as it is.
	 *
	 * @param array<string, mixed> $init      TinyMCE settings.
	 * @param string               $editor_id The editor's id.
	 * @return array<string, mixed>
	 */
	public static function settings( array $init, string $editor_id ): array {
		if ( ! isset( self::TOOLBARS[ $editor_id ] ) ) {
			return $init;
		}
		$init['menubar']   = false;
		$init['statusbar'] = false;
		$init['toolbar1']  = self::TOOLBARS[ $editor_id ];
		foreach ( array( 'toolbar2', 'toolbar3', 'toolbar4' ) as $row ) {
			$init[ $row ] = '';
		}
		// Lasso's Quill editors say "Enter a description" and "$99.99" while empty. TinyMCE 4.9 has no placeholder of its own,
		// so the words travel as a setting and a style inside the editor draws them; product.js toggles the class and the
		// attribute.
		$style = 'body.affibloom-empty::before { content: attr(data-placeholder); color: #6b7280; position: absolute; pointer-events: none; }';
		if ( 'affibloom_description' === $editor_id ) {
			$init['placeholder'] = __( 'Enter a description', 'affibloom' );
		}
		// Lasso's price editor is one line the size of its other inputs. TinyMCE's own minimum (100px) and WordPress's growing
		// editor would make it taller, and the editor's stylesheets (WordPress's and the theme's) give the body a margin and a
		// larger font that overflow one line; the style matches .affibloom-input (36px inside its 1px border, 14px/20px text).
		// Single quotes only: WordPress prints each setting into the page's script as key:"value" without escaping.
		if ( 'affibloom_price' === $editor_id ) {
			$init['min_height']       = 36;
			$init['height']           = 36;
			$init['wp_autoresize_on'] = false;
			$init['placeholder']      = '$99.99';
			$style                   .= " html body#tinymce { margin: 0; padding: 8px 12px; color: #14213d; font: 400 14px/20px -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif; } html body#tinymce > p { margin: 0; }";
		}
		$init['content_style'] = ( isset( $init['content_style'] ) && is_string( $init['content_style'] ) ? $init['content_style'] . ' ' : '' ) . $style;

		return $init;
	}
}
