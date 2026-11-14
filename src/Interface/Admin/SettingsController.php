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
	 * Where the guard's "Save Changes" may go afterwards: an address inside this site's own admin, nothing else (an
	 * empty string otherwise, so the save falls back to its own tab).
	 *
	 * @param string $url The address the form carried.
	 */
	public static function admin_target( string $url ): string {
		if ( '' === $url ) {
			return '';
		}

		$safe = wp_validate_redirect( $url, '' );

		return str_starts_with( $safe, admin_url() ) ? $safe : '';
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

		// The unsaved-changes guard's "Save Changes" saves and then goes where the reader was going (Task 9E). A save
		// that was refused in part stays on the tab, so the refused fields are seen (F9 edge case 8).
		$goto = isset( $_POST['redirect_to'] ) ? self::admin_target( esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) ) : '';
		if ( '' !== $goto && ! $refused ) {
			wp_safe_redirect( $goto );
			exit;
		}

		$query = array(
			'page'             => SettingsScreen::SLUG,
			'tab'              => $tab,
			'affibloom_notice' => $refused ? 'partly-saved' : 'saved',
		);
		if ( array_key_exists( SettingsService::SUPPORT_MARKER, $input ) ) {
			// The support view stays open after its own save.
			$query['support'] = '1';
		}
		if ( $refused ) {
			// The fields that kept their old value, so the screen can mark them.
			$query['refused'] = implode( ',', $refused );
		}

		wp_safe_redirect( add_query_arg( $query, admin_url( 'admin.php' ) ) );
		exit;
	}
}
