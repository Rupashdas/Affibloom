<?php
/**
 * The DB Status page (Task TL4).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Application\Settings\SettingsService;
use Affibloom\Infrastructure\Schema\AffibloomTables;
use Affibloom\Interface\Template;
use RuntimeException;

/**
 * The DB Status page (F37 R9-R11, D4, AC5; Task TL4): `admin.php?page=affibloom-db-status`, hidden, in the Settings
 * band with no tab current. Loading it creates or brings up to date every Affibloom table, as Lasso's page runs its
 * create script, and lists each with what happened: "Table already exists." or dbDelta's lines.
 */
final class DbStatusScreen {

	/** The page's slug. */
	public const SLUG = 'affibloom-db-status';

	/**
	 * Builds the screen.
	 *
	 * @param Template        $template The templates.
	 * @param SettingsService $settings The settings, for the band's tabs.
	 */
	public function __construct( private Template $template, private SettingsService $settings ) {}

	/**
	 * Prints the page.
	 */
	public function render(): void {
		global $wpdb;

		$rows = array();
		foreach ( AffibloomTables::all() as $table ) {
			try {
				$changes = $table->create();
				$output  = array() === $changes ? array( __( 'Table already exists.', 'affibloom' ) ) : $changes;
			} catch ( RuntimeException $failed ) {
				$output = array( $failed->getMessage() );
			}
			$rows[] = array(
				'name'   => $wpdb->prefix . $table->name(),
				'output' => $output,
			);
		}

		$this->template->render(
			'admin/db-status',
			array(
				'tabs' => $this->settings->tabs(),
				'rows' => $rows,
			)
		);
	}
}
