<?php
/**
 * Saves one Settings tab (admin-post.php).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Application\Settings\SettingsService;
use Affibloom\Module;

/**
 * Saves one Settings tab (admin-post.php).
 */
final class SettingsController implements Module {

	public const ACTION = 'affibloom_save_settings';

	/**
	 * Builds the controller.
	 *
	 * @param SettingsService $settings Settings use cases.
	 */
	public function __construct( private SettingsService $settings ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'admin_post_' . self::ACTION, array( $this, 'save' ) );
	}

	/**
	 * Validates, saves, and redirects back to the same tab with a notice.
	 */
	public function save(): void {
		if ( ! current_user_can( Capabilities::SETTINGS ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to change these settings.', 'affibloom' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::ACTION );

		$tab = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : '';
		if ( ! in_array( $tab, $this->settings->tabs(), true ) ) {
			wp_die( esc_html__( 'Unknown settings tab.', 'affibloom' ), '', array( 'response' => 400 ) );
		}

		// Each field is sanitised and validated by type in SettingsService.
		$input = isset( $_POST['settings'] ) && is_array( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$refused = $this->settings->update_tab( $tab, $input );

		$query = array(
			'page'             => SettingsScreen::SLUG,
			'tab'              => $tab,
			'affibloom_notice' => $refused ? 'partly-saved' : 'saved',
		);
		if ( $refused ) {
			// The fields that kept their old value, so the screen can mark them.
			$query['refused'] = implode( ',', $refused );
		}

		wp_safe_redirect( add_query_arg( $query, admin_url( 'admin.php' ) ) );
		exit;
	}
}
