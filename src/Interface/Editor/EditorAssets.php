<?php
/**
 * What the block editor needs for Affibloom's displays: the display modal's styles and markup, and where its script is.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Editor;

use Affibloom\Interface\Front\BoxAssets;
use Affibloom\Interface\Template;
use Affibloom\Module;

/**
 * The display modal on block-editor screens (F4 D1, Task 23A; Lasso's R57 without its libraries): Meadow's fonts and
 * admin styles plus the modal's own, the modal's markup in the footer, and the address of its script for the block,
 * which loads it on first use (blocks/product-box/modal-loader.js). In the editor's canvas, the box's own styles, so a
 * preview looks as the page will (R34, Task 23C).
 */
final class EditorAssets implements Module {

	/** The block's editor script, as WordPress names it from block.json (generate_block_asset_handle). */
	public const BLOCK_SCRIPT = 'affibloom-product-box-editor-script';

	/**
	 * The modal's steps that exist; a type whose step is missing is shown "Soon" in the chooser. Each insert task adds its
	 * step (23B links, 23D groups, F25 tables).
	 *
	 * @var list<string>
	 */
	public const STEPS = array( 'links', 'groups' );

	/**
	 * Builds the assets.
	 *
	 * @param string    $plugin_url The plugin's URL, with a trailing slash.
	 * @param string    $version    The plugin's version, for cache busting.
	 * @param Template  $template   Prints the modal's markup.
	 * @param BoxAssets $box        The box's stylesheets, for the canvas.
	 */
	public function __construct( private string $plugin_url, private string $version, private Template $template, private BoxAssets $box ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue' ) );
		add_action( 'admin_footer', array( $this, 'print_modal' ) );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_canvas' ) );
	}

	/**
	 * The box's styles in the editor's canvas (`enqueue_block_assets` also runs on the site, which BoxAssets serves).
	 */
	public function enqueue_canvas(): void {
		if ( is_admin() ) {
			$this->box->enqueue_for_editor();
		}
	}

	/**
	 * The modal's styles (the design system's, which its root's `.affibloom-admin` scopes) and its script's address.
	 */
	public function enqueue(): void {
		wp_enqueue_style( 'affibloom-fonts', $this->plugin_url . 'assets/admin/fonts.css', array(), $this->version );
		wp_enqueue_style( 'affibloom-admin', $this->plugin_url . 'assets/admin/admin.css', array( 'affibloom-fonts' ), $this->version );
		wp_enqueue_style( 'affibloom-display-modal', $this->plugin_url . 'assets/editor/display-modal.css', array( 'affibloom-admin' ), $this->version );

		$config = array( 'modalScript' => add_query_arg( 'ver', $this->version, $this->plugin_url . 'assets/editor/display-modal.js' ) );
		wp_add_inline_script( self::BLOCK_SCRIPT, 'window.affibloomEditor = ' . wp_json_encode( $config ) . ';', 'before' );
	}

	/**
	 * The modal's markup, hidden until a block opens it; on block-editor screens only.
	 */
	public function print_modal(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( null === $screen || ! $screen->is_block_editor() ) {
			return;
		}
		$this->template->render( 'editor/display-modal', array( 'steps' => self::STEPS ) );
	}
}
