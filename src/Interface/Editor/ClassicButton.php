<?php
/**
 * The Classic editor's "Add A Display" button.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Editor;

use Affibloom\Module;

/**
 * Lasso's TinyMCE button (F4 R60-R63, D11; Task ED1), with Affibloom's name: only while the Classic Editor or Disable
 * Gutenberg plugin is active (Lasso's is_classic_editor()), the toolbar gets **Add A Display**, which opens the same
 * display modal as the block and inserts the display's shortcode at the cursor (no preview). Lasso's undefined grid
 * button and its keyword element are not built (D11). EditorAssets prints the modal and its styles on those screens.
 */
final class ClassicButton implements Module {

	/** The TinyMCE plugin's and button's name. */
	public const PLUGIN = 'affibloom_display';

	/** The plugins that bring the Classic editor back, as Lasso checks them. */
	private const CLASSIC_PLUGINS = array( 'classic-editor/classic-editor.php', 'disable-gutenberg/disable-gutenberg.php' );

	/**
	 * Builds the button.
	 *
	 * @param string $plugin_url The plugin's URL, with a trailing slash.
	 * @param string $version    The plugin's version, for cache busting.
	 */
	public function __construct( private string $plugin_url, private string $version ) {}

	/**
	 * Whether the Classic editor is in use on this site (Lasso's is_classic_editor()).
	 */
	public static function active(): bool {
		return array() !== array_intersect( self::CLASSIC_PLUGINS, (array) get_option( 'active_plugins', array() ) );
	}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_filter( 'mce_external_plugins', array( $this, 'plugins' ) );
		add_filter( 'mce_buttons', array( $this, 'buttons' ) );
	}

	/**
	 * TinyMCE's external plugins: ours, on the Classic editor only.
	 *
	 * @param array<string, string> $plugins Plugin names and their scripts.
	 * @return array<string, string>
	 */
	public function plugins( array $plugins ): array {
		if ( self::active() && current_user_can( 'edit_posts' ) ) {
			$plugins[ self::PLUGIN ] = add_query_arg( 'ver', $this->version, $this->plugin_url . 'assets/editor/classic-button.js' );
		}

		return $plugins;
	}

	/**
	 * The first toolbar row, with the button at its end.
	 *
	 * @param list<string> $buttons The buttons.
	 * @return list<string>
	 */
	public function buttons( array $buttons ): array {
		if ( self::active() && current_user_can( 'edit_posts' ) ) {
			$buttons[] = self::PLUGIN;
		}

		return $buttons;
	}
}
