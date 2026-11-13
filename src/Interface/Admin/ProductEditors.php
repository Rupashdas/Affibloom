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
		// Lasso's price editor is one line; TinyMCE's own minimum (100px) and WordPress's growing editor would make it more.
		if ( 'affibloom_price' === $editor_id ) {
			$init['min_height']       = 40;
			$init['height']           = 40;
			$init['wp_autoresize_on'] = false;
		}

		return $init;
	}
}
