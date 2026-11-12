<?php
/**
 * A background job as a WP-Cron single event (ADR-4).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Application\Products\BackgroundJob;

/**
 * A background job as a WP-Cron single event (ADR-4).
 */
final class WpCronBackgroundJob implements BackgroundJob {

	/**
	 * Builds the job.
	 *
	 * @param string $hook The cron hook whose handler does the work.
	 */
	public function __construct( private string $hook ) {}

	/**
	 * Schedules the event for now unless it is already waiting, then asks WordPress to run due events at once.
	 * spawn_cron() ignores DISABLE_WP_CRON (that constant only stops the spawn on every page load), refuses while a
	 * cron run is going and spawns at most once a minute; a refusal leaves the event for the next run.
	 */
	public function dispatch(): void {
		if ( false === wp_next_scheduled( $this->hook ) ) {
			wp_schedule_single_event( time(), $this->hook );
		}
		spawn_cron();
	}
}
