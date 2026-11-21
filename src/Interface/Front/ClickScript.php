<?php
/**
 * The click counter's script on the pages that need it.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Front;

use Affibloom\Module;

/**
 * Prints `assets/front/clicks.js` where a reader can click a product (F12 D3, AC17, AC19; Task 33A): when a box is drawn
 * (BoxAssets calls enqueue()) or the content holds a tied link (`the_content` at 999, after the links are stamped at
 * 20). Its settings, the route and the page's post, are the same for every visitor, so cached pages count. Nothing in
 * the admin or the editor's previews.
 */
final class ClickScript implements Module {

	/** The script's handle. */
	public const HANDLE = 'affibloom-clicks';

	/**
	 * Builds the loader.
	 *
	 * @param string $url     The plugin's address, with a trailing slash.
	 * @param string $version The plugin's version.
	 */
	public function __construct( private string $url, private string $version ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_filter( 'the_content', array( $this, 'maybe_enqueue' ), 999 );
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
		wp_enqueue_script( self::HANDLE, $this->url . 'assets/front/clicks.js', array(), $this->version, true );
		$settings = array(
			'url'  => rest_url( 'affibloom/v1/clicks' ),
			'post' => is_singular() ? (int) get_queried_object_id() : 0,
		);
		wp_add_inline_script( self::HANDLE, 'window.affibloomClicks = ' . wp_json_encode( $settings ) . ';', 'before' );
	}
}
