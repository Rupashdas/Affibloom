<?php
/**
 * Lasso's rich text editors, as WordPress's TinyMCE settings.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

/**
 * The rich text editors (WordPress's TinyMCE) with Lasso's Quill toolbars (url-details.php 562-577 and 363-378): the
 * product page's description and price (Task 14B), and a comparison table's "Description Editor" and price cells (F25
 * D10, Task T7; taken out of the product page on that second use). Another plugin can rewrite every editor's settings:
 * Advanced Editor Tools, active on this site, adds its menu bar and toolbars through tiny_mce_before_init. These editors
 * take their settings last, so they keep Lasso's whatever else is installed. A table's price cell is made in the page
 * (wp.editor.initialize), so it takes for() as its settings directly.
 */
final class RichEditorSettings {

	/** Lasso's description toolbar. */
	public const DESCRIPTION = 'bold,italic,underline,strikethrough,link,bullist,forecolor,backcolor,removeformat';

	/** Lasso's one-line price toolbar. */
	public const PRICE = 'bold,italic,underline,strikethrough,forecolor';

	/** The editors printed by wp_editor(): id => kind. */
	private const EDITORS = array(
		'affibloom_description'       => 'description',
		'affibloom_price'             => 'price',
		'affibloom_table_description' => 'description',
	);

	/**
	 * Applies the settings to the editors printed after this call.
	 */
	public static function apply(): void {
		add_filter( 'tiny_mce_before_init', array( self::class, 'settings' ), PHP_INT_MAX, 2 );
	}

	/**
	 * One editor's TinyMCE settings: Affibloom's editors get Lasso's toolbar, no other row and no menu bar; every other
	 * editor is left as it is.
	 *
	 * @param array<string, mixed> $init      TinyMCE settings.
	 * @param string               $editor_id The editor's id.
	 * @return array<string, mixed>
	 */
	public static function settings( array $init, string $editor_id ): array {
		if ( ! isset( self::EDITORS[ $editor_id ] ) ) {
			return $init;
		}
		$style = isset( $init['content_style'] ) && is_string( $init['content_style'] ) ? $init['content_style'] : '';

		return array_merge( $init, self::for( self::EDITORS[ $editor_id ], $style ) );
	}

	/**
	 * Lasso's settings for one kind of editor.
	 *
	 * @param string      $kind          'description' or 'price'.
	 * @param string      $content_style The style the editor has already, kept first.
	 * @param string|null $placeholder   The words shown while it is empty; Lasso's for the kind when null.
	 * @return array<string, mixed>
	 */
	public static function for( string $kind, string $content_style = '', ?string $placeholder = null ): array {
		$init = array(
			'menubar'   => false,
			'statusbar' => false,
			'toolbar1'  => 'price' === $kind ? self::PRICE : self::DESCRIPTION,
			'toolbar2'  => '',
			'toolbar3'  => '',
			'toolbar4'  => '',
		);
		// Lasso's Quill editors say "Enter a description" and "$99.99" while empty. TinyMCE 4.9 has no placeholder of its own,
		// so the words travel as a setting and a style inside the editor draws them; admin.js toggles the class and the
		// attribute (affibloomEditorPlaceholder).
		$style = 'body.affibloom-empty::before { content: attr(data-placeholder); color: #6b7280; position: absolute; pointer-events: none; }';
		if ( 'description' === $kind ) {
			$init['placeholder'] = $placeholder ?? __( 'Enter a description', 'affibloom' );
		}
		// Lasso's price editor is one line the size of its other inputs. TinyMCE's own minimum (100px) and WordPress's growing
		// editor would make it taller, and the editor's stylesheets (WordPress's and the theme's) give the body a margin and a
		// larger font that overflow one line; the style matches .affibloom-input (36px inside its 1px border, 14px/20px text).
		// Single quotes only: WordPress prints each setting into the page's script as key:"value" without escaping.
		if ( 'price' === $kind ) {
			$init['min_height']       = 36;
			$init['height']           = 36;
			$init['wp_autoresize_on'] = false;
			$init['placeholder']      = $placeholder ?? '$99.99';
			$style                   .= " html body#tinymce { margin: 0; padding: 8px 12px; color: #14213d; font: 400 14px/20px -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif; } html body#tinymce > p { margin: 0; }";
		}
		$init['content_style'] = ( '' !== $content_style ? $content_style . ' ' : '' ) . $style;

		return $init;
	}
}
