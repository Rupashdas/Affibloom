<?php
/**
 * Saves one display type's configuration (admin-post.php).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Application\Display\DisplaySettingsService;
use Affibloom\Domain\Display\DisplayType;
use Affibloom\Module;

/**
 * Saves one display type's configuration (admin-post.php).
 */
final class DisplayController implements Module {

	public const ACTION = 'affibloom_save_display';

	/**
	 * Builds the controller.
	 *
	 * @param DisplaySettingsService $display Display use cases.
	 */
	public function __construct( private DisplaySettingsService $display ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'admin_post_' . self::ACTION, array( $this, 'save' ) );
	}

	/**
	 * Validates, saves, and redirects back to the same display type with a notice.
	 */
	public function save(): void {
		if ( ! current_user_can( Capabilities::SETTINGS ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to change these settings.', 'affibloom' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::ACTION );

		$type = DisplayType::from( isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '' );

		// Each value is cleaned by type in DisplaySettingsService.
		$input = isset( $_POST['display'] ) && is_array( $_POST['display'] ) ? wp_unslash( $_POST['display'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$refused = $this->display->update_type( $type, $input );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'             => SettingsScreen::SLUG,
					'tab'              => 'display',
					'type'             => $type->key(),
					'affibloom_notice' => $refused ? 'partly-saved' : 'saved',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
