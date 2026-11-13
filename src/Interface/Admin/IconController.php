<?php
/**
 * Adds and removes button icons from Settings → Display (F28).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Application\Icons\IconService;
use Affibloom\Domain\InvalidField;
use Affibloom\Module;

/**
 * Adds and removes button icons from Settings → Display's "Button icons" card (F28 requirements B2-B3), through
 * admin-post.php, then back to the card with a toast.
 */
final class IconController implements Module {

	public const ADD    = 'affibloom_add_icon';
	public const REMOVE = 'affibloom_remove_icon';

	/** The image types an icon may be (F28 requirements B2). */
	private const TYPES = array( 'image/svg+xml', 'image/png', 'image/webp' );

	/**
	 * Builds the controller.
	 *
	 * @param IconService $icons The icon set.
	 */
	public function __construct( private IconService $icons ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'admin_post_' . self::ADD, array( $this, 'add' ) );
		add_action( 'admin_post_' . self::REMOVE, array( $this, 'remove' ) );
	}

	/**
	 * POST name, attachment: a new icon. The attachment must be an SVG, PNG or WebP image the editor can see.
	 */
	public function add(): void {
		$this->authorise();
		check_admin_referer( self::ADD );

		$name       = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$attachment = isset( $_POST['attachment'] ) ? absint( wp_unslash( $_POST['attachment'] ) ) : 0;
		$url        = $attachment > 0 && current_user_can( 'read_post', $attachment ) && wp_attachment_is_image( $attachment )
			&& in_array( get_post_mime_type( $attachment ), self::TYPES, true ) ? (string) wp_get_attachment_url( $attachment ) : '';

		try {
			$this->icons->add( $name, esc_url_raw( $url ), $attachment );
			$this->back( 'icon-added' );
		} catch ( InvalidField ) {
			$this->back( 'icon-refused' );
		}
	}

	/**
	 * POST class: removes an icon added here (an imported one is the site's CSS and stays).
	 */
	public function remove(): void {
		$this->authorise();
		check_admin_referer( self::REMOVE );

		$class = isset( $_POST['class'] ) ? sanitize_key( wp_unslash( $_POST['class'] ) ) : '';
		try {
			$this->icons->remove( $class );
			$this->back( 'icon-removed' );
		} catch ( InvalidField ) {
			$this->back( 'icon-kept' );
		}
	}

	/**
	 * Only people who manage the settings change the icon set.
	 */
	private function authorise(): void {
		if ( ! current_user_can( Capabilities::SETTINGS ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to change these settings.', 'affibloom' ), '', array( 'response' => 403 ) );
		}
	}

	/**
	 * Back to the card, with the toast's code.
	 *
	 * @param string $notice The Notice code.
	 */
	private function back( string $notice ): void {
		$url = add_query_arg(
			array(
				'page'             => SettingsScreen::SLUG,
				'tab'              => 'display',
				'affibloom_notice' => $notice,
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url . '#affibloom-icon-set' );
		exit;
	}
}
