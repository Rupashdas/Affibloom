<?php
/**
 * Settings → Display: one configuration per display type.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Application\Display\DisplaySettingsService;
use Affibloom\Application\Icons\IconService;
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
	 * @param IconService            $icons    The button icons (the "Button icons" card, F28).
	 */
	public function __construct(
		private Template $template,
		private DisplaySettingsService $display,
		private SettingsService $settings,
		private Notice $notice,
		private IconService $icons,
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
				'icons'    => $this->icon_rows(),
			)
		);
	}

	/**
	 * The "Button icons" card's rows (F28, Task 14F): each icon with a link the admin can show. A media library item
	 * gives its own; otherwise the link is the one the site's CSS writes, made absolute (the site writes
	 * "/wp-content/…" and, once, "wp-content/…", which inside wp-admin would point into /wp-admin/).
	 *
	 * @return list<array{icon: \Affibloom\Domain\Icons\Icon, src: string}>
	 */
	private function icon_rows(): array {
		$rows = array();
		foreach ( $this->icons->all()->all() as $icon ) {
			$src    = $icon->attachment > 0 ? (string) wp_get_attachment_url( $icon->attachment ) : '';
			$src    = '' !== $src ? $src : ( 1 === preg_match( '#^https?://#i', $icon->url ) ? $icon->url : home_url( '/' . ltrim( $icon->url, '/' ) ) );
			$rows[] = array(
				'icon' => $icon,
				'src'  => $src,
			);
		}
		return $rows;
	}
}
