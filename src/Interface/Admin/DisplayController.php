<?php
/**
 * Saves the Display screen and resets it (admin-post.php).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Application\Display\DisplaySettingsService;
use Affibloom\Application\Settings\SettingsService;
use Affibloom\Domain\Display\DisplayType;
use Affibloom\Module;

/**
 * Saves the Display screen and resets it (admin-post.php).
 *
 * The screen edits two stores, as Lasso's does: the per-type display model, and the site-wide display settings
 * (disclosure, Show Price, Site Stripe, Custom CSS), which live with the other settings.
 */
final class DisplayController implements Module {

	public const ACTION       = 'affibloom_save_display';
	public const RESET_ACTION = 'affibloom_reset_display';

	/**
	 * Builds the controller.
	 *
	 * @param DisplaySettingsService $display  Display use cases.
	 * @param SettingsService        $settings Site-wide settings use cases.
	 */
	public function __construct( private DisplaySettingsService $display, private SettingsService $settings ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'admin_post_' . self::ACTION, array( $this, 'save' ) );
		add_action( 'admin_post_' . self::RESET_ACTION, array( $this, 'reset' ) );
	}

	/**
	 * Validates, saves, and redirects back to the same display type and rail tab with a notice.
	 */
	public function save(): void {
		$this->authorise();
		check_admin_referer( self::ACTION );

		$type = DisplayType::from( isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '' );

		// Each value is cleaned by type in DisplaySettingsService and SettingsService.
		$input = isset( $_POST['display'] ) && is_array( $_POST['display'] ) ? wp_unslash( $_POST['display'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$flat  = isset( $_POST['settings'] ) && is_array( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		// Lasso's one "Disclosure in Lists & Tables" toggle writes both keys (display.php 676-677).
		$flat['show_disclosure_table'] = $flat['show_disclosure_list'] ?? null;

		$refused = array_merge(
			$this->display->update_type( $type, $input ),
			$this->settings->update_tab( 'display', $flat )
		);

		// The unsaved-changes guard's "Save Changes" goes on to where the reader was going (Task 9E).
		$goto = isset( $_POST['redirect_to'] ) ? SettingsController::admin_target( esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) ) : '';
		if ( '' !== $goto && ! $refused ) {
			wp_safe_redirect( $goto );
			exit;
		}

		$this->back( $type->key(), isset( $_POST['panel'] ) && 'colors' === $_POST['panel'] ? 'colors' : 'layout', $refused ? 'partly-saved' : 'saved' );
	}

	/**
	 * Puts every Display setting back to Lasso's factory defaults, as its Reset Settings does.
	 */
	public function reset(): void {
		$this->authorise();
		check_admin_referer( self::RESET_ACTION, '_affibloom_reset_nonce' );

		$this->display->reset();
		$this->settings->reset_tab( 'display' );

		$type = DisplayType::from( isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '' );
		$this->back( $type->key(), 'layout', 'reset' );
	}

	/**
	 * Stops anyone who may not change settings. Each handler checks its own nonce right after.
	 */
	private function authorise(): void {
		if ( ! current_user_can( Capabilities::SETTINGS ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to change these settings.', 'affibloom' ), '', array( 'response' => 403 ) );
		}
	}

	/**
	 * Back to the Display screen, on the same type and rail tab, with a notice.
	 *
	 * @param string $type   Display type key.
	 * @param string $panel  Rail tab key.
	 * @param string $notice Notice code.
	 */
	private function back( string $type, string $panel, string $notice ): void {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'             => SettingsScreen::SLUG,
					'tab'              => 'display',
					'type'             => $type,
					'panel'            => $panel,
					'affibloom_notice' => $notice,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
