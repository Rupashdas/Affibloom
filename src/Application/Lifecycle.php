<?php
/**
 * What activating, deactivating and updating the plugin does (F37 D1, D2).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application;

use Closure;
use Affibloom\Application\Upgrade\Upgrader;

/**
 * What activating, deactivating and updating the plugin does (F37 D1, D2): as Lasso's activator, deactivator and
 * version check, for what Affibloom has. None of it removes products, settings or any other data; that is left to
 * uninstalling.
 */
final class Lifecycle {

	/**
	 * Builds the lifecycle.
	 *
	 * @param Schedules   $schedules Every Affibloom WP-Cron hook.
	 * @param PluginState $state     The plugin's own records and the site's rewrite rules.
	 * @param Upgrader    $upgrader  The pending schema and data steps.
	 * @param string      $version   The running plugin version (AFFIBLOOM_VERSION).
	 * @param Closure|null $activated Called at the end of an activation (the link index's first rebuild, Task 35F).
	 */
	public function __construct(
		private Schedules $schedules,
		private PluginState $state,
		private Upgrader $upgrader,
		private string $version,
		private ?Closure $activated = null
	) {}

	/**
	 * Records the version and the activation time, runs the pending steps, then rebuilds the rewrite rules. A failed
	 * step does not stop the activation: the next admin page runs it again and says why it failed.
	 */
	public function activate(): void {
		$this->state->record_version( $this->version );
		$this->state->record_activation_time();
		$this->upgrader->run();
		$this->state->flush_rewrite_rules();
		if ( null !== $this->activated ) {
			( $this->activated )();
		}
	}

	/**
	 * Clears every scheduled Affibloom event, so none runs while the plugin is off, then rebuilds the rewrite rules.
	 */
	public function deactivate(): void {
		$this->schedules->clear();
		$this->state->flush_rewrite_rules();
	}

	/**
	 * After a zip with a new version replaced the plugin (no activation runs then): rebuilds the rewrite rules once
	 * and stores the version (Lasso's R27).
	 */
	public function watch_version(): void {
		if ( $this->state->stored_version() === $this->version ) {
			return;
		}
		$this->state->flush_rewrite_rules();
		$this->state->record_version( $this->version );
	}
}
