<?php
/**
 * Affibloom's displays in Elementor.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Editor;

use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Interface\Front\BoxAssets;
use Affibloom\Module;
use Elementor\Widgets_Manager;

/**
 * Lasso's Elementor integration (F4 R65-R71, Task ED2): the widget "Affibloom", and its assets inside Elementor's
 * editor only (R71; Elementor's own editor hooks, where Lasso read the referrer): the display modal's styles, markup and
 * address in the editor's window, the widget's script there (assets/editor/elementor.js), and the box's styles in the
 * canvas, so a preview looks as the page will. The hooks fire only while Elementor is active.
 */
final class ElementorWidget implements Module {

	/** The widget's script and stylesheet. */
	public const HANDLE = 'affibloom-elementor';

	/**
	 * Builds the integration.
	 *
	 * @param string       $plugin_dir The plugin's folder, with a trailing slash.
	 * @param string       $plugin_url The plugin's URL, with a trailing slash.
	 * @param string       $version    The plugin's version, for cache busting.
	 * @param EditorAssets $assets     The display modal's styles, words and markup.
	 * @param BoxAssets    $box        The box's stylesheets, for the canvas.
	 */
	public function __construct( private string $plugin_dir, private string $plugin_url, private string $version, private EditorAssets $assets, private BoxAssets $box ) {}

	/**
	 * Hooks into Elementor.
	 */
	public function register(): void {
		add_action( 'elementor/widgets/register', array( $this, 'widgets' ) );
		add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'enqueue_editor' ) );
		add_action( 'elementor/editor/footer', array( $this, 'print_modal' ) );
		add_action( 'elementor/preview/enqueue_styles', array( $this, 'enqueue_canvas' ) );
	}

	/**
	 * The widget, for the editor and for the pages that render it.
	 *
	 * @param Widgets_Manager $widgets Elementor's widgets.
	 */
	public function widgets( Widgets_Manager $widgets ): void {
		$widgets->register( new ElementorDisplayWidget() );
	}

	/**
	 * The editor's window: the widget's script, after the modal's address and words, and its stylesheet (the panel's
	 * icon); for those who may place displays. The script is built with the block's (webpack.config.js), which shares
	 * the preview queue; its asset file names its WordPress packages. Not built: nothing is added, as the block is not
	 * registered.
	 */
	public function enqueue_editor(): void {
		$asset_file = $this->plugin_dir . 'build/elementor.asset.php';
		if ( ! current_user_can( Capabilities::INSERT_BOXES ) || ! is_readable( $asset_file ) ) {
			return;
		}
		$asset        = require $asset_file;
		$dependencies = is_array( $asset ) && is_array( $asset['dependencies'] ?? null ) ? $asset['dependencies'] : array();
		wp_enqueue_script(
			self::HANDLE,
			$this->plugin_url . 'build/elementor.js',
			// Elementor's editor script defines window.elementor, which the widget's waits for.
			array_merge( $dependencies, array( 'elementor-editor' ) ),
			is_array( $asset ) && is_string( $asset['version'] ?? null ) ? $asset['version'] : $this->version,
			true
		);
		wp_enqueue_style( self::HANDLE, $this->plugin_url . 'assets/editor/elementor.css', array(), $this->version );
		$this->assets->enqueue_for( self::HANDLE );
	}

	/**
	 * The modal's markup in the editor's footer, for those who may place displays.
	 */
	public function print_modal(): void {
		if ( current_user_can( Capabilities::INSERT_BOXES ) ) {
			$this->assets->modal();
		}
	}

	/**
	 * The canvas: the widget's panel styles and the box's.
	 */
	public function enqueue_canvas(): void {
		wp_enqueue_style( self::HANDLE, $this->plugin_url . 'assets/editor/elementor.css', array(), $this->version );
		$this->box->enqueue_for_editor();
	}
}
