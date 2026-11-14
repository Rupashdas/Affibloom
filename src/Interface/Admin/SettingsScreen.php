<?php
/**
 * Settings: General, Display and Amazon tabs, as in Lasso.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Application\Settings\DetectionChoices;
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
	 * @param Template         $template Template renderer.
	 * @param SettingsService  $settings Settings use cases.
	 * @param DisplayScreen    $display  The Display tab, which has its own model and screen.
	 * @param Notice           $notice   The toast after a save.
	 * @param DetectionChoices $choices What Custom Link Detection can scan (General).
	 */
	public function __construct(
		private Template $template,
		private SettingsService $settings,
		private DisplayScreen $display,
		private Notice $notice,
		private DetectionChoices $choices
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

		$refused = isset( $_GET['refused'] ) ? array_map( 'sanitize_key', explode( ',', sanitize_text_field( wp_unslash( $_GET['refused'] ) ) ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.

		$this->template->render(
			'amazon' === $tab ? 'admin/settings-amazon' : 'admin/settings',
			array(
				'tab'        => $tab,
				'tabs'       => $tabs,
				'settings'   => $this->settings->current(),
				'action'     => SettingsController::ACTION,
				'notice'     => $this->notice->message(),
				'refused'    => $refused,
				// The support view of General (`&support=1`, Task 9L; Lasso's `&support`), which only shows more fields.
				'support'    => isset( $_GET['support'] ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chooses what to display only.
				'post_types' => 'general' === $tab ? $this->choices->post_types() : array(),
				'fields'     => 'general' === $tab ? $this->choices->custom_fields() : array(),
				'has_acf'    => 'general' === $tab && $this->choices->has_acf(),
			)
		);
	}
}
