<?php
/**
 * What activating and deactivating the plugin does (F37 D1).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application;

/**
 * What activating and deactivating the plugin does (F37 D1): as Lasso's activator and deactivator, for what
 * Affibloom has. Neither removes products, settings or any other data; that is left to uninstalling.
 */
final class Lifecycle {

	/**
	 * Builds the lifecycle.
	 *
	 * @param Schedules   $schedules Every Affibloom WP-Cron hook.
	 * @param PluginState $state     The plugin's own records and the site's rewrite rules.
	 * @param string      $version   The running plugin version (AFFIBLOOM_VERSION).
	 */
	public function __construct(
		private Schedules $schedules,
		private PluginState $state,
		private string $version
	) {}

	/**
	 * Records the version and the activation time, then rebuilds the rewrite rules.
	 */
	public function activate(): void {
		$this->state->record_activation( $this->version );
		$this->state->flush_rewrite_rules();
	}

	/**
	 * Clears every scheduled Affibloom event, so none runs while the plugin is off, then rebuilds the rewrite rules.
	 */
	public function deactivate(): void {
		$this->schedules->clear();
		$this->state->flush_rewrite_rules();
	}
}
