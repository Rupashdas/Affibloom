<?php
/**
 * The setup wizard (Tasks ON6, ON7).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Application\Amazon\CredentialsService;
use Affibloom\Application\Display\DisplaySettingsService;
use Affibloom\Application\Settings\SettingsService;
use Affibloom\Domain\Display\DisplayType;
use Affibloom\Interface\Template;

/**
 * The setup wizard (F38 R47-R56, D17, AC10, AC11; Tasks ON6, ON7): `admin.php?page=affibloom-setup`, hidden, Lasso's
 * steps without Activate (decision B1) and Imports (deviation 146): Theme, Customize, Amazon, Analytics, Done. Each step
 * reuses its screen's own fields and saves only them (SettingsService's hidden `setup_*` tabs); setup.js moves between
 * the steps. It opens by itself once on a new site (SetupRedirect) and can be opened by its address at any time.
 */
final class SetupScreen {

	/** The page's slug. */
	public const SLUG = 'affibloom-setup';

	/**
	 * Builds the screen.
	 *
	 * @param Template               $template    The templates.
	 * @param SettingsService        $settings    The settings.
	 * @param DisplaySettingsService $display     The display settings (the single box's theme and colours).
	 * @param IconChoices            $icons       The button texts' icon choices.
	 * @param CredentialsService     $credentials The Product Data API card.
	 */
	public function __construct( private Template $template, private SettingsService $settings, private DisplaySettingsService $display, private IconChoices $icons, private CredentialsService $credentials ) {}

	/**
	 * Prints the wizard.
	 */
	public function render(): void {
		$this->template->render(
			'admin/setup',
			array(
				'settings' => $this->settings->current(),
				'config'   => $this->display->current()->config( DisplayType::single() ),
				'choices'  => $this->icons,
				'api'      => SettingsScreen::api_card( $this->credentials ),
			)
		);
	}
}
