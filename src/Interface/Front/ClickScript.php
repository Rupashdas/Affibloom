<?php
/**
 * The click counter's script on the pages that need it.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Front;

use Affibloom\Domain\Analytics\GaSettings;
use Affibloom\Domain\Settings\SettingsRepository;
use Affibloom\Module;

/**
 * Prints `assets/front/clicks.js` where a reader can click a product (F12 D3, AC17, AC19; Task 33A): when a box is drawn
 * (BoxAssets calls enqueue()) or the content holds a tied link (`the_content` at 999, after the links are stamped at
 * 20). Its settings, the route and the page's post, are the same for every visitor, so cached pages count. Nothing in
 * the admin or the editor's previews. It is printed while Page and Link-Level Tracking (F12 AC9; Task 33D) or GA Click
 * Tracking (C3 D6; Task C3B) is on, and its settings say which of the two to do.
 */
final class ClickScript implements Module {

	/** The script's handle. */
	public const HANDLE = 'affibloom-clicks';

	/** The attributes that keep page optimisers from delaying or combining the click script (script_attributes()). */
	private const KEEP = array(
		'data-no-defer'    => '1',
		'data-no-optimize' => '1',
		'data-cfasync'     => 'false',
	);

	/**
	 * Builds the loader.
	 *
	 * @param string             $url      The plugin's address, with a trailing slash.
	 * @param string             $version  The plugin's version.
	 * @param SettingsRepository $settings The site's settings: Page and Link-Level Tracking (Task 33D).
	 */
	public function __construct( private string $url, private string $version, private SettingsRepository $settings ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_filter( 'the_content', array( $this, 'maybe_enqueue' ), 999 );
		add_filter( 'wp_script_attributes', array( $this, 'script_attributes' ) );
		add_filter( 'wp_inline_script_attributes', array( $this, 'inline_attributes' ) );
	}

	/**
	 * Marks the click script for page optimisers to leave alone (Task 37D, finding C1): LiteSpeed's "Load JS Deferred:
	 * Delayed", production's setting, held it until the reader's first move, so a first click on a box was not counted.
	 * `data-no-defer` and `data-no-optimize` are LiteSpeed's (no delay; no minify or combine), `data-cfasync` is
	 * Cloudflare Rocket Loader's. The script is 2 KB and waits for nothing.
	 *
	 * @param array<string, string|bool> $attributes The script tag's attributes.
	 * @return array<string, string|bool>
	 */
	public function script_attributes( array $attributes ): array {
		return self::HANDLE . '-js' === ( $attributes['id'] ?? '' ) ? $attributes + self::KEEP : $attributes;
	}

	/**
	 * The same for the line before it that gives the script its settings (`affibloomClicks`).
	 *
	 * @param array<string, string|bool> $attributes The inline script tag's attributes.
	 * @return array<string, string|bool>
	 */
	public function inline_attributes( array $attributes ): array {
		return self::HANDLE . '-js-before' === ( $attributes['id'] ?? '' ) ? $attributes + self::KEEP : $attributes;
	}

	/**
	 * The content's tied links ask for the script (35H stamps `data-affibloom-id`).
	 *
	 * @param mixed $html The content.
	 * @return mixed The content, unchanged.
	 */
	public function maybe_enqueue( mixed $html ): mixed {
		if ( is_string( $html ) && str_contains( $html, 'data-affibloom-id' ) ) {
			$this->enqueue();
		}

		return $html;
	}

	/**
	 * Enqueues the script with its settings, once; in the footer, so a box drawn late in the page still gets it.
	 */
	public function enqueue(): void {
		if ( is_admin() || wp_script_is( self::HANDLE, 'enqueued' ) ) {
			return;
		}
		$site  = $this->settings->get();
		$count = $site->bool( 'click_tracking' );
		$ga    = GaSettings::of( $site )->clicks;
		if ( ! $count && ! $ga ) {
			return;
		}
		wp_enqueue_script( self::HANDLE, $this->url . 'assets/front/clicks.js', array(), $this->version, true );
		$settings = array(
			'url'   => rest_url( 'affibloom/v1/clicks' ),
			'post'  => is_singular() ? (int) get_queried_object_id() : 0,
			'count' => $count,
			'ga'    => $ga,
		);
		wp_add_inline_script( self::HANDLE, 'window.affibloomClicks = ' . wp_json_encode( $settings ) . ';', 'before' );
	}
}
