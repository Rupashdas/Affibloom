<?php
/**
 * Settings: General, Display and Amazon tabs, as in Lasso.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Application\Settings\SettingsService;
use Affibloom\Interface\Template;

/**
 * Settings: General, Display and Amazon tabs, as in Lasso.
 */
final class SettingsScreen {

	public const SLUG = 'affibloom-settings';

	/**
	 * Builds the screen.
	 *
	 * @param Template        $template Template renderer.
	 * @param SettingsService $settings Settings use cases.
	 * @param DisplayScreen   $display  The Display tab, which has its own model and screen.
	 * @param SettingsNotice  $notice   The toast after a save.
	 */
	public function __construct(
		private Template $template,
		private SettingsService $settings,
		private DisplayScreen $display,
		private SettingsNotice $notice
	) {}

	/**
	 * Prints the current tab.
	 */
	public function render(): void {
		$tabs = $this->settings->tabs();
		$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chooses what to display only.
		if ( ! in_array( $tab, $tabs, true ) ) {
			$tab = 'general';
		}

		if ( 'display' === $tab ) {
			$this->display->render( $tabs );
			return;
		}

		$this->template->render(
			'admin/settings',
			array(
				'tab'      => $tab,
				'tabs'     => $tabs,
				'settings' => $this->settings->current(),
				'action'   => SettingsController::ACTION,
				'notice'   => $this->notice->message(),
			)
		);
	}
}
