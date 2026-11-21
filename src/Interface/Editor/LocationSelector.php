<?php
/**
 * "Edit in post": the editor opens with the link selected.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Editor;

use Affibloom\Domain\LinkIndex\LinkLocationRepository;
use Affibloom\Module;

/**
 * F22 N1-B (Task O10): an opportunity's "Edit in post" opens the post's editor with `affibloom_location=<row>`; this
 * module hands the row's address and occurrence to assets/editor/select-location.js, which selects the block holding
 * that link (block editor) or the link itself (classic editor), so the editor can change it by hand. Only for someone
 * who may edit that post, and only for a link in the post's own content.
 */
final class LocationSelector implements Module {

	/**
	 * Builds the module.
	 *
	 * @param LinkLocationRepository $rows       The link index.
	 * @param string                 $plugin_url The plugin's URL, with a trailing slash.
	 * @param string                 $version    The asset version.
	 */
	public function __construct( private LinkLocationRepository $rows, private string $plugin_url, private string $version ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * On the post editor with `affibloom_location`, the script and the link to select.
	 *
	 * @param string $hook_suffix The admin page.
	 */
	public function enqueue( string $hook_suffix ): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- selects a link on screen; nothing is changed.
		if ( 'post.php' !== $hook_suffix || ! isset( $_GET['affibloom_location'], $_GET['post'] ) ) {
			return;
		}
		$row  = absint( wp_unslash( $_GET['affibloom_location'] ) );
		$post = absint( wp_unslash( $_GET['post'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$place = $this->rows->find( $row );
		if ( null === $place || $place->host_id !== $post || 'content' !== $place->source || ! current_user_can( 'edit_post', $post ) ) {
			return;
		}

		wp_enqueue_script( 'affibloom-select-location', $this->plugin_url . 'assets/editor/select-location.js', array(), $this->version, true );
		wp_add_inline_script(
			'affibloom-select-location',
			'window.affibloomSelectLocation = ' . (string) wp_json_encode(
				array(
					'url'        => $place->url,
					'occurrence' => $place->occurrence,
				)
			) . ';',
			'before'
		);
	}
}
