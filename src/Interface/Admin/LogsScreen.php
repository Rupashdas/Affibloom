<?php
/**
 * The Logs page (Task TL3).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Application\Logs\LogFiles;
use Affibloom\Application\Settings\SettingsService;
use Affibloom\Domain\Logs\LogType;
use Affibloom\Interface\Template;

/**
 * The Logs page (F37 R1-R8, AC3; Task TL3): `admin.php?page=affibloom-logs`, hidden from the menu, in the Settings
 * band with no tab current. Enable Logs saves through the settings route's hidden `logs` tab.
 */
final class LogsScreen {

	/** The page's slug. */
	public const SLUG = 'affibloom-logs';

	/**
	 * Builds the screen.
	 *
	 * @param Template        $template The templates.
	 * @param SettingsService $settings The settings.
	 * @param LogFiles        $logs     The stored logs.
	 * @param Notice          $notice   The page's notice.
	 */
	public function __construct( private Template $template, private SettingsService $settings, private LogFiles $logs, private Notice $notice ) {}

	/**
	 * Prints the page.
	 */
	public function render(): void {
		$logs = array();
		foreach ( LogType::cases() as $type ) {
			$logs[ $type->value ] = $this->logs->newest( $type );
		}

		$this->template->render(
			'admin/logs',
			array(
				'tabs'      => $this->settings->tabs(),
				'settings'  => $this->settings->current(),
				'rest_path' => '/affibloom/v1/settings/tabs/logs',
				'notice'    => $this->notice->message(),
				'logs'      => $logs,
			)
		);
	}
}
