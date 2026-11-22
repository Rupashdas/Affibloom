<?php
/**
 * The Ctrl+Shift+L product search.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Interface\Template;
use Affibloom\Module;

/**
 * "Search links" (F38 R65-R72, D19; Task ON9), Lasso's command palette: on every Affibloom screen and on the posts'
 * lists and editors, for whoever may open a product page (its results lead there). Loads its script and styles there
 * and prints the dialog as a `<template>` for the script; nothing is printed elsewhere.
 */
final class CommandPalette implements Module {

	/** The lists and editors of posts, pages and other post types (R65). */
	private const EDITOR_SCREENS = array( 'edit.php', 'post.php', 'post-new.php' );

	/**
	 * Whether this request's screen loaded the palette, so the footer prints its dialog.
	 *
	 * @var bool
	 */
	private bool $loaded = false;

	/**
	 * Builds the module.
	 *
	 * @param Template $template   The templates.
	 * @param string   $plugin_url The plugin's URL, with a trailing slash.
	 * @param string   $version    The asset version.
	 */
	public function __construct( private Template $template, private string $plugin_url, private string $version ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'admin_footer', array( $this, 'dialog' ) );
	}

	/**
	 * Loads the palette on its screens for people who may open products.
	 *
	 * @param string $hook_suffix Current admin page hook, e.g. "toplevel_page_affibloom" or "post.php".
	 */
	public function enqueue( string $hook_suffix ): void {
		if ( ! str_contains( $hook_suffix, 'affibloom' ) && ! in_array( $hook_suffix, self::EDITOR_SCREENS, true ) ) {
			return;
		}
		if ( ! current_user_can( Capabilities::PRODUCTS ) ) {
			return;
		}
		$this->loaded = true;
		// Its own styles: the editors do not load admin.css.
		wp_enqueue_style( 'affibloom-fonts', $this->plugin_url . 'assets/admin/fonts.css', array(), $this->version );
		wp_enqueue_style( 'affibloom-palette', $this->plugin_url . 'assets/admin/palette.css', array( 'affibloom-fonts' ), $this->version );
		wp_enqueue_script( 'affibloom-palette', $this->plugin_url . 'assets/admin/palette.js', array( 'wp-api-fetch' ), $this->version, true );
	}

	/**
	 * Prints the dialog's template where the palette was loaded.
	 */
	public function dialog(): void {
		if ( ! $this->loaded ) {
			return;
		}
		$this->template->render(
			'admin/partials/palette',
			array( 'product_url' => add_query_arg( 'page', ProductScreen::SLUG, admin_url( 'admin.php' ) ) )
		);
	}
}
