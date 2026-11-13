<?php
/**
 * Settings → Display: one configuration per display type.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Application\Display\DisplaySettingsService;
use Affibloom\Application\Settings\SettingsService;
use Affibloom\Domain\Display\DisplayType;
use Affibloom\Interface\Template;

/**
 * Settings → Display: one configuration per display type.
 */
final class DisplayScreen {

	/**
	 * Builds the screen.
	 *
	 * @param Template               $template Template renderer.
	 * @param DisplaySettingsService $display  Display use cases.
	 * @param SettingsService        $settings The site-wide display settings (disclosure, Show Price, Custom CSS).
	 * @param Notice                 $notice   The toast after a save.
	 * @param IconChoices            $icons    The button icons: the "Button icons" card and the pickers (F28).
	 */
	public function __construct(
		private Template $template,
		private DisplaySettingsService $display,
		private SettingsService $settings,
		private Notice $notice,
		private IconChoices $icons,
	) {}

	/**
	 * Prints the Display tab for the type in the query.
	 *
	 * @param list<string> $tabs The Settings tabs, for the tab strip.
	 */
	public function render( array $tabs ): void {
		$raw  = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chooses what to display only.
		$type = DisplayType::from( $raw );

		// The rail tab to open: Lasso opens Layout & spacing; after a save, the tab the editor was on.
		$panel = isset( $_GET['panel'] ) && 'colors' === $_GET['panel'] ? 'colors' : 'layout'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chooses what to display only.

		$this->template->render(
			'admin/settings-display',
			array(
				'tab'      => 'display',
				'tabs'     => $tabs,
				'type'     => $type,
				'types'    => DisplayType::all(),
				'config'   => $this->display->config( $type ),
				'columns'  => $this->display->current()->columns(),
				'action'   => DisplayController::ACTION,
				'reset'    => DisplayController::RESET_ACTION,
				'settings' => $this->settings->current(),
				'notice'   => $this->notice->message(),
				'panel'    => $panel,
				'icons'    => $this->icons->rows(),
				'choices'  => $this->icons,
			)
		);
	}
}
