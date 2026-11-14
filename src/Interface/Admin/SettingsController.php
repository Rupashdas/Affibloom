<?php
/**
 * Saves one Settings tab (admin-post.php).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Application\Amazon\CredentialsService;
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
	 * @param SettingsService    $settings    Settings use cases.
	 * @param CredentialsService $credentials The Amazon tab's Product Data API card (C1A).
	 */
	public function __construct( private SettingsService $settings, private CredentialsService $credentials ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'admin_post_' . self::ACTION, array( $this, 'save' ) );
		// Save Changes sends the form by AJAX, as Lasso does (settings-save.js); the form alone still uses admin-post.
		add_action( 'wp_ajax_' . self::ACTION, array( $this, 'save' ) );
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
	 * The toast after a save: refused fields first, then credentials Save Changes could not validate (R23).
	 *
	 * @param list<string> $refused    Keys that kept their old value.
	 * @param string|null  $unverified CredentialsService::SAVE_KEPT or SAVE_CLEARED, or null.
	 */
	private static function notice( array $refused, ?string $unverified ): string {
		if ( $refused ) {
			return 'partly-saved';
		}

		return null === $unverified ? 'saved' : 'amazon-unverified-' . $unverified;
	}

	/**
	 * The Product Data API card's four fields as posted (`amazon_api[…]`). The Credential ID and Secret are kept as typed
	 * (trimmed later), since any character may be part of a secret; the others are plain text.
	 *
	 * @return array<string, string>
	 */
	private static function api_fields(): array {
		$posted = isset( $_POST['amazon_api'] ) && is_array( $_POST['amazon_api'] ) ? wp_unslash( $_POST['amazon_api'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- save() checked the nonce; each field is cleaned below.
		$text   = static fn( string $key ): string => isset( $posted[ $key ] ) && is_string( $posted[ $key ] ) ? $posted[ $key ] : '';

		return array(
			'credential_id' => $text( 'credential_id' ),
			'secret'        => $text( 'secret' ),
			'partner_tag'   => sanitize_text_field( $text( 'partner_tag' ) ),
			'version'       => sanitize_text_field( $text( 'version' ) ),
		);
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

		// The Amazon tab's Product Data API card: its credentials have their own option, saved after the tab (whose
		// Default Tracking ID they are validated for), and validated when they are not yet (C1 R23).
		$unverified = 'amazon' === $tab ? $this->credentials->save( self::api_fields() ) : null;

		// The unsaved-changes guard's "Save Changes" saves and then goes where the reader was going (Task 9E). A save
		// that was refused in part stays on the tab, so the refused fields are seen (F9 edge case 8).
		$goto = isset( $_POST['redirect_to'] ) ? self::admin_target( esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) ) : '';
		if ( '' !== $goto && ! $refused && null === $unverified ) {
			SaveAnswer::go( $goto );
		}

		$query = array(
			'page'             => SettingsScreen::SLUG,
			'tab'              => $tab,
			'affibloom_notice' => self::notice( $refused, $unverified ),
		);
		if ( array_key_exists( SettingsService::SUPPORT_MARKER, $input ) ) {
			// The support view stays open after its own save.
			$query['support'] = '1';
		}
		if ( $refused ) {
			// The fields that kept their old value, so the screen can mark them.
			$query['refused'] = implode( ',', $refused );
		}

		SaveAnswer::go( add_query_arg( $query, admin_url( 'admin.php' ) ) );
	}
}
