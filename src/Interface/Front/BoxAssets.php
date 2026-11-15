<?php
/**
 * The product box's stylesheets and the page's colour and Custom CSS block.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Front;

use Affibloom\Application\Display\DisplayPresentation;
use Affibloom\Application\Display\DisplaySettingsService;
use Affibloom\Domain\Display\DisplayType;
use Affibloom\Domain\Settings\CustomCss;
use Affibloom\Domain\Settings\SettingsRepository;
use Affibloom\Module;

/**
 * What a box needs on the page (F5 R42, R43; Task 18B): `assets/front/box.css` and, after it, `box-layout.css` (the
 * advanced box's rules), versioned by their file times as Lasso's are; and in `<head>` one style element with the
 * `:root` colours and density of the Single type, then the editor's Custom CSS. Where they load (Smart CSS Loading,
 * the late enqueue) is Task 18C's.
 */
final class BoxAssets implements Module {

	/** The stylesheets' handles. */
	public const STYLE        = 'affibloom-box';
	public const LAYOUT_STYLE = 'affibloom-box-layout';

	/**
	 * Builds the loader.
	 *
	 * @param string                 $dir      The plugin's folder, with a trailing slash.
	 * @param string                 $url      The plugin's address, with a trailing slash.
	 * @param DisplaySettingsService $display  The Display settings.
	 * @param SettingsRepository     $settings The site's settings (Custom CSS).
	 */
	public function __construct(
		private readonly string $dir,
		private readonly string $url,
		private readonly DisplaySettingsService $display,
		private readonly SettingsRepository $settings,
	) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'wp_head', array( $this, 'print_head' ) );
	}

	/**
	 * Enqueues the stylesheets. Every box is advanced until classic rendering exists (Task 18K), so both load.
	 */
	public function enqueue(): void {
		wp_enqueue_style( self::STYLE, $this->url . 'assets/front/box.css', array(), $this->version( 'assets/front/box.css' ) );
		wp_enqueue_style( self::LAYOUT_STYLE, $this->url . 'assets/front/box-layout.css', array( self::STYLE ), $this->version( 'assets/front/box-layout.css' ) );
	}

	/**
	 * Prints the `<head>` block.
	 */
	public function print_head(): void {
		$display = $this->display->current();
		$root    = DisplayPresentation::root( $display->config( DisplayType::single() ), $display->columns() );

		echo self::head_style( $root, $this->settings->get()->string( 'custom_css' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS: the properties come from validated settings, the Custom CSS through CustomCss::clean(), which removes `<`.
	}

	/**
	 * Lasso's style element: the `:root` block, then, after a line break, the Custom CSS (cleaned again here, so
	 * nothing in it can close the element).
	 *
	 * @param string $root   The `:root{…}` block.
	 * @param string $custom The stored Custom CSS.
	 */
	public static function head_style( string $root, string $custom ): string {
		$custom = CustomCss::clean( $custom );
		$css    = '' === $custom ? $root : $root . "\n" . $custom;

		return '<style type="text/css">' . $css . "</style>\n";
	}

	/**
	 * A file's version: its modification time, as Lasso versions its stylesheets (R42), so a changed file is fetched
	 * again.
	 *
	 * @param string $file The file, from the plugin's folder.
	 */
	private function version( string $file ): string {
		$time = filemtime( $this->dir . $file );

		return false === $time ? '' : (string) $time;
	}
}
