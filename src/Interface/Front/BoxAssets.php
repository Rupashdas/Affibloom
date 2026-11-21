<?php
/**
 * The product box's stylesheets and scripts, where and when they load, and the page's colour and Custom CSS block.
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
use Affibloom\Infrastructure\WordPress\BoxDetector;
use Affibloom\Module;

/**
 * What a box needs on the page, by Lasso's rules (F5 R42-R43, R45-R51; Tasks 18B, 18C):
 *
 * - `assets/front/box.css`, then `box-layout.css` (the advanced box's rules) and the phone-layout script
 *   `box-layout.js`, versioned by their file times as Lasso's are; in `<head>` one style element with the `:root`
 *   colours and density of the Single type, then the editor's Custom CSS.
 * - Smart CSS Loading (`smart_css`, on by default; decision C5): a page the detector finds no box on gets none of
 *   that; off, every front-end page gets it. Every box drawn enqueues it as a safety net (a widget, a theme template),
 *   printed in the footer when late, with the `<head>` block added inline when it was skipped; once per request.
 * - On every page the Font Awesome config (`fontawesome_svg`) and the body class `affibloom-v<version>`; on AMP pages
 *   the inline CSS; with Jetpack, the boxes' own images kept original (F31 D11; Lasso keeps every image of the page).
 *
 * Every box is advanced until classic rendering exists (Task 18K), so the layout rules and script always come along.
 * Lasso's copy-to-clipboard script, which it loads on every page, loads with the box (Task 21C, deviation 136).
 */
final class BoxAssets implements Module {

	/** The stylesheets' and script's handles. */
	public const STYLE        = 'affibloom-box';
	public const LAYOUT_STYLE = 'affibloom-box-layout';
	public const TABLE_STYLE  = 'affibloom-table';
	public const LAYOUT_JS    = 'affibloom-box-layout';
	public const COPY_JS      = 'affibloom-copy-clipboard';

	/**
	 * Whether this request's page needs the box's assets, once asked.
	 *
	 * @var bool|null
	 */
	private ?bool $needed = null;

	/**
	 * Whether the `<head>` block was printed.
	 *
	 * @var bool
	 */
	private bool $head_printed = false;

	/**
	 * Whether a drawn box has already made sure of its assets.
	 *
	 * @var bool
	 */
	private bool $late_done = false;

	/**
	 * The drawn boxes' images, which Jetpack's image CDN leaves alone (F31 D11).
	 *
	 * @var array<string, true>
	 */
	private array $originals = array();

	/**
	 * Whether the AMP CSS was printed.
	 *
	 * @var bool
	 */
	private bool $amp_printed = false;

	/**
	 * Builds the loader.
	 *
	 * @param string                 $dir      The plugin's folder, with a trailing slash.
	 * @param string                 $url      The plugin's address, with a trailing slash.
	 * @param string                 $version  The plugin's version (the body class).
	 * @param DisplaySettingsService $display  The Display settings.
	 * @param SettingsRepository     $settings The site's settings.
	 * @param BoxDetector            $detector Whether the page shows a box.
	 * @param ClickScript|null       $clicks   The click counter, wherever a box is (F12 D3; Task 33A).
	 */
	public function __construct(
		private readonly string $dir,
		private readonly string $url,
		private readonly string $version,
		private readonly DisplaySettingsService $display,
		private readonly SettingsRepository $settings,
		private readonly BoxDetector $detector,
		private readonly ?ClickScript $clicks = null,
	) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'wp_head', array( $this, 'print_head' ) );
		add_filter( 'body_class', array( $this, 'body_class' ) );
		add_action( 'amp_post_template_css', array( $this, 'print_amp_css' ), 1000 );
	}

	/**
	 * Enqueues the box's assets when the page needs them (R45).
	 */
	public function enqueue(): void {
		if ( $this->needed() ) {
			$this->enqueue_box();
		}
	}

	/**
	 * Prints the `<head>` block when the page needs it (R43, R46), then the Font Awesome config, which every page gets
	 * (R49).
	 */
	public function print_head(): void {
		if ( $this->needed() ) {
			echo '<style type="text/css">' . $this->head_css() . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS: the properties come from validated settings, the Custom CSS through CustomCss::clean(), which removes `<`.
			$this->head_printed = true;
		}

		echo self::font_awesome_config( $this->settings->get()->bool( 'fontawesome_svg' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- a fixed script with a boolean.
	}

	/**
	 * The safety net (R47), called for every box drawn: the assets are enqueued (WordPress prints a late stylesheet in
	 * the footer), with the `<head>` block inline when it was skipped. Jetpack's image CDN is told to keep the box's
	 * own image original (R50; F31 D11 narrows Lasso's every image of the page). Once per request; nothing in the admin
	 * (the previews load their own, Tasks 10D and 14E).
	 *
	 * @param string $image The box's image address; '' none.
	 */
	public function late_enqueue( string $image = '' ): void {
		if ( is_admin() ) {
			return;
		}
		if ( '' !== $image ) {
			$this->originals[ $image ] = true;
		}
		if ( $this->late_done ) {
			return;
		}
		$this->late_done = true;

		$this->enqueue_box();
		if ( ! $this->head_printed ) {
			wp_add_inline_style( self::STYLE, $this->head_css() );
		}
		add_filter( 'jetpack_photon_skip_for_url', array( $this, 'photon_skip' ), 1000, 2 );
	}

	/**
	 * The box's stylesheets and the `<head>` block's CSS in the block editor's canvas (F4 R34, Task 23C), so a block's
	 * preview looks as the published page will. EditorAssets calls it on `enqueue_block_assets` in the admin.
	 */
	public function enqueue_for_editor(): void {
		$this->enqueue_box();
		wp_add_inline_style( self::STYLE, $this->head_css() );
	}

	/**
	 * Jetpack's `jetpack_photon_skip_for_url`: true for a drawn box's image, else Jetpack's own answer.
	 *
	 * @param mixed $skip Jetpack's answer.
	 * @param mixed $url  The image's address.
	 * @return mixed
	 */
	public function photon_skip( mixed $skip, mixed $url ): mixed {
		return is_string( $url ) && isset( $this->originals[ $url ] ) ? true : $skip;
	}

	/**
	 * Adds `affibloom-v<version>` to the front end's body classes (R51, D13).
	 *
	 * @param array<int, string> $classes The body classes.
	 * @return array<int, string>
	 */
	public function body_class( array $classes ): array {
		$classes[] = 'affibloom-v' . $this->version;

		return $classes;
	}

	/**
	 * AMP's legacy templates take CSS only inline (R50): the `:root` block without `!important`, the Custom CSS and
	 * `box-amp.css`, its image addresses made absolute; once.
	 */
	public function print_amp_css(): void {
		if ( $this->amp_printed ) {
			return;
		}
		$this->amp_printed = true;

		$display = $this->display->current();
		$amp     = (string) file_get_contents( $this->dir . 'assets/front/box-amp.css' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a file of the plugin's own.

		echo DisplayPresentation::root( $display->config( DisplayType::single() ), $display->columns(), false ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS from validated settings.
			. CustomCss::clean( $this->settings->get()->string( 'custom_css' ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- cleaned: no `<`.
			. str_replace( "url('images/", "url('" . $this->url . 'assets/front/images/', $amp ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the plugin's own CSS.
	}

	/**
	 * The `<head>` block's CSS: the `:root` block, each value `!important` as Lasso prints it, then, after a line break,
	 * the Custom CSS, cleaned again here so nothing in it can close the style element.
	 *
	 * @param string $root   The `:root{…}` block.
	 * @param string $custom The stored Custom CSS.
	 */
	public static function head_style( string $root, string $custom ): string {
		$custom = CustomCss::clean( $custom );

		return '' === $custom ? $root : $root . "\n" . $custom;
	}

	/**
	 * Lasso's Font Awesome config (R49): whether Font Awesome's script, if another plugin loads it, turns icons into
	 * SVGs.
	 *
	 * @param bool $svg The `fontawesome_svg` setting.
	 */
	public static function font_awesome_config( bool $svg ): string {
		return '<script type="text/javascript">window.FontAwesomeConfig = { autoReplaceSvg: ' . ( $svg ? 'true' : 'false' ) . " }</script>\n";
	}

	/**
	 * Whether this page needs the box's assets: always with Smart CSS Loading off; on, when the detector finds a box.
	 */
	private function needed(): bool {
		if ( null === $this->needed ) {
			$this->needed = ! $this->settings->get()->bool( 'smart_css' ) || $this->detector->page_needs_css();
		}

		return $this->needed;
	}

	/**
	 * Enqueues the stylesheets and the phone-layout script.
	 */
	private function enqueue_box(): void {
		wp_enqueue_style( self::STYLE, $this->url . 'assets/front/box.css', array(), $this->file_version( 'assets/front/box.css' ) );
		// F25 R52 (Task T12): the comparison tables' stylesheet, with the box's, as Lasso loads its own.
		wp_enqueue_style( self::TABLE_STYLE, $this->url . 'assets/front/table.css', array( self::STYLE ), $this->file_version( 'assets/front/table.css' ) );
		// R36 (deviation 136): the Copy to Clipboard button's script, in the footer, wherever a box is.
		wp_enqueue_script( self::COPY_JS, $this->url . 'assets/front/copy-clipboard.js', array(), $this->file_version( 'assets/front/copy-clipboard.js' ), true );
		// F12 D3 (Task 33A): a box's buttons, title and image count their clicks.
		$this->clicks?->enqueue();
		// R64: the classic box loads neither the advanced stylesheet nor the phone-layout script.
		if ( ! $this->display->current()->advanced() ) {
			return;
		}
		wp_enqueue_style( self::LAYOUT_STYLE, $this->url . 'assets/front/box-layout.css', array( self::STYLE ), $this->file_version( 'assets/front/box-layout.css' ) );
		wp_enqueue_script( self::LAYOUT_JS, $this->url . 'assets/front/box-layout.js', array(), $this->file_version( 'assets/front/box-layout.js' ), true );
	}

	/**
	 * The `<head>` block's CSS for this site (also a table preview's frame, F25 Task T15).
	 */
	public function head_css(): string {
		$display = $this->display->current();

		return self::head_style(
			DisplayPresentation::root( $display->config( DisplayType::single() ), $display->columns(), true ),
			$this->settings->get()->string( 'custom_css' )
		);
	}

	/**
	 * The box's stylesheets with their versions, for a preview frame (Tasks 10D, 14E): box.css, and the advanced
	 * stylesheet when the settings render advanced.
	 *
	 * @param bool $advanced Whether the preview renders advanced.
	 * @return list<string>
	 */
	public function stylesheets( bool $advanced ): array {
		$sheets = array( 'assets/front/box.css' );
		if ( $advanced ) {
			$sheets[] = 'assets/front/box-layout.css';
		}

		return array_map( fn( string $file ): string => add_query_arg( 'ver', $this->file_version( $file ), $this->url . $file ), $sheets );
	}

	/**
	 * The comparison tables' stylesheet with its version, for a table preview's frame (F25 R52; Task T15).
	 */
	public function table_stylesheet(): string {
		return add_query_arg( 'ver', $this->file_version( 'assets/front/table.css' ), $this->url . 'assets/front/table.css' );
	}

	/**
	 * The phone-layout script with its version, for a preview frame (R48; Tasks 10D, 14E).
	 */
	public function phone_script(): string {
		return add_query_arg( 'ver', $this->file_version( 'assets/front/box-layout.js' ), $this->url . 'assets/front/box-layout.js' );
	}

	/**
	 * A file's version: its modification time, as Lasso versions its files (R42), so a changed file is fetched again.
	 *
	 * @param string $file The file, from the plugin's folder.
	 */
	private function file_version( string $file ): string {
		$time = filemtime( $this->dir . $file );

		return false === $time ? '' : (string) $time;
	}
}
