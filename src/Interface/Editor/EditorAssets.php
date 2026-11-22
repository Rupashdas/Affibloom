<?php
/**
 * What the block editor needs for Affibloom's displays: the display modal's styles and markup, and where its script is.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Editor;

use Affibloom\Interface\Admin\Capabilities;
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
	 * Builds the assets.
	 *
	 * @param string        $plugin_url The plugin's URL, with a trailing slash.
	 * @param string        $version    The plugin's version, for cache busting.
	 * @param Template      $template   Prints the modal's markup.
	 * @param BoxAssets     $box        The box's stylesheets, for the canvas.
	 * @param \Closure|null $icons      The icon library as the pickers list it (IconChoices::listing; Task 24G).
	 * @phpstan-param (\Closure(): list<array{class: string, name: string, src: string, missing: bool}>)|null $icons
	 */
	public function __construct( private string $plugin_url, private string $version, private Template $template, private BoxAssets $box, private ?\Closure $icons = null ) {}

	/**
	 * The icon library for the editors' badge and button texts (Task 24G): the block's picker, Elementor's dropdowns.
	 *
	 * @return list<array{class: string, name: string, src: string, missing: bool}>
	 */
	public function icons(): array {
		return null === $this->icons ? array() : ( $this->icons )();
	}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue' ) );
		add_action( 'admin_footer', array( $this, 'print_modal' ) );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_canvas' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_classic' ) );
		// After Rank Math registers its analyzer (on admin_enqueue_scripts, priority 10).
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_rank_math' ), 20 );
	}

	/**
	 * Rank Math's content analysis reads the blocks' previews (Task ED3, F4 R73-R74): assets/editor/rank-math.js, on
	 * block-editor screens where Rank Math's analyzer is registered, as Lasso's check.
	 */
	public function enqueue_rank_math(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( null === $screen || ! $screen->is_block_editor() || ! wp_script_is( 'rank-math-analyzer', 'registered' ) ) {
			return;
		}
		wp_enqueue_script( 'affibloom-rank-math', $this->plugin_url . 'assets/editor/rank-math.js', array( 'wp-hooks', 'rank-math-analyzer' ), $this->version, true );
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
		$this->enqueue_for( self::BLOCK_SCRIPT );
		// The table builder's icon grid for the sidebar's badge and button texts (Task 24G). Without admin.js, which
		// carries the media picker and the toasts, it offers no "Add icon"; icons are added in Settings → Display.
		wp_enqueue_script( 'affibloom-icon-popover', $this->plugin_url . 'assets/admin/icon-popover.js', array( 'wp-api-fetch' ), $this->version, true );
	}

	/**
	 * The modal's styles, and `window.affibloomEditor` before a script that opens the modal: the block's, the Classic
	 * editor's button's (Task ED1) or the Elementor widget's (ED2).
	 *
	 * @param string $handle The script's handle.
	 */
	public function enqueue_for( string $handle ): void {
		$this->styles();
		wp_add_inline_script( $handle, $this->config(), 'before' );
	}

	/**
	 * The Classic editor's screens (Task ED1): while ClassicButton is active, the same styles and the modal's address for
	 * the TinyMCE button, with WordPress's apiFetch, which the Classic editor does not load itself.
	 *
	 * @param string $hook_suffix The admin page, e.g. "post.php".
	 */
	public function enqueue_classic( string $hook_suffix ): void {
		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) || ! ClassicButton::active() ) {
			return;
		}
		wp_register_script( 'affibloom-editor-config', false, array( 'wp-api-fetch' ), $this->version, false );
		wp_enqueue_script( 'affibloom-editor-config' );
		$this->enqueue_for( 'affibloom-editor-config' );
	}

	/**
	 * Meadow's fonts and admin styles, then the modal's.
	 */
	private function styles(): void {
		wp_enqueue_style( 'affibloom-fonts', $this->plugin_url . 'assets/admin/fonts.css', array(), $this->version );
		wp_enqueue_style( 'affibloom-admin', $this->plugin_url . 'assets/admin/admin.css', array( 'affibloom-fonts' ), $this->version );
		wp_enqueue_style( 'affibloom-display-modal', $this->plugin_url . 'assets/editor/display-modal.css', array( 'affibloom-admin' ), $this->version );
	}

	/**
	 * `window.affibloomEditor`: the modal's address, the sidebar's fields (Task 24A) with the icon library and the icon
	 * picker's words (24G), the Classic button's words (ED1) and the Elementor widget's (ED2).
	 */
	private function config(): string {
		$config = array(
			'modalScript' => add_query_arg( 'ver', $this->version, $this->plugin_url . 'assets/editor/display-modal.js' ),
			'overrides'   => OverrideTexts::catalog(),
			'icons'       => $this->icons(),
			// The table builder's words for its icon picker (table-inspector.php).
			'iconLabels'  => array(
				'dialog' => __( 'Choose an icon', 'affibloom' ),
				'search' => __( 'Search icons', 'affibloom' ),
				'none'   => __( 'No icon', 'affibloom' ),
				'empty'  => __( 'No icons match.', 'affibloom' ),
				/* translators: 1: the field's label, 2: the icon's name, "No icon" or an unknown class. */
				'button' => __( 'Icon for %1$s: %2$s', 'affibloom' ),
			),
			// Lasso's button words (R61-R62), with Affibloom's name in place of Lasso's.
			'classic'     => array(
				'add'     => __( 'Add A Display', 'affibloom' ),
				'loading' => __( 'Loading Display...', 'affibloom' ),
				'retry'   => __( 'Retry Add A Display', 'affibloom' ),
			),
			// The Elementor widget's (ED2): the block's words.
			'elementor'   => array(
				'loading' => __( 'Loading…', 'affibloom' ),
				'retry'   => __( 'Retry Add a Display', 'affibloom' ),
				'empty'   => __( 'This display has nothing to show: its product is gone or not published.', 'affibloom' ),
				'failed'  => __( 'The preview could not be loaded.', 'affibloom' ),
			),
		);

		return 'window.affibloomEditor = ' . wp_json_encode( $config ) . ';';
	}

	/**
	 * The modal's markup, hidden until a block (or the Classic editor's button, ED1) opens it; on editor screens only.
	 */
	public function print_modal(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( null !== $screen && ( $screen->is_block_editor() || ( 'post' === $screen->base && ClassicButton::active() ) ) ) {
			$this->modal();
		}
	}

	/**
	 * The modal's markup; the Elementor editor prints it in its footer (ED2).
	 */
	public function modal(): void {
		$this->template->render(
			'editor/display-modal',
			array(
				// Create Group and Add to Group need PRODUCTS (F4 D8); without it they are not printed.
				'can_manage' => current_user_can( Capabilities::PRODUCTS ),
			)
		);
	}
}
