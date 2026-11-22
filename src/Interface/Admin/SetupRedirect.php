<?php
/**
 * Opens the setup wizard on the first visit after a new site's activation (Task ON6).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Application\Shell\SetupState;
use Affibloom\Module;

/**
 * Opens the setup wizard on the first visit to an Affibloom page after a new site's activation (F38 N1, D13, AC10;
 * Task ON6), once, for a user who may change settings. `affibloom_setup_wanted` lets another plugin say no: the Lasso
 * Migrator does on a site with Lasso's data (Phase M; deviation 153), as Affibloom's core does not read Lasso's.
 */
final class SetupRedirect implements Module {

	/**
	 * Builds the redirect.
	 *
	 * @param SetupState $state When the wizard is due.
	 */
	public function __construct( private SetupState $state ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'admin_init', array( $this, 'maybe_open' ) );
	}

	/**
	 * Sends the first visit to an Affibloom page to the wizard.
	 */
	public function maybe_open(): void {
		$page = AdminMenu::requested_page();
		if ( wp_doing_ajax() || ! str_starts_with( $page, AdminMenu::SLUG ) || SetupScreen::SLUG === $page || ! current_user_can( Capabilities::SETTINGS ) ) {
			return;
		}
		if ( ! (bool) apply_filters( 'affibloom_setup_wanted', true ) || ! $this->state->take_first_visit() ) {
			return;
		}
		wp_safe_redirect( admin_url( 'admin.php?page=' . SetupScreen::SLUG ) );
		exit;
	}
}
