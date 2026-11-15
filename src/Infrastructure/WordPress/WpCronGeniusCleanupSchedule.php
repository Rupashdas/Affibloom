<?php
/**
 * The geni.us clean-up job's next run, as a WP-Cron single event.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Application\Products\GeniusCleanupSchedule;

/**
 * The geni.us clean-up job's next run as a WP-Cron single event (ADR-4), its argument the ID the run goes on below.
 */
final class WpCronGeniusCleanupSchedule implements GeniusCleanupSchedule {

	/**
	 * Builds the schedule.
	 *
	 * @param string $hook The cron hook whose handler runs the job.
	 */
	public function __construct( private string $hook ) {}

	/**
	 * Queues the run for now and asks WordPress to run due events (as WpCronBackgroundJob::dispatch()). A new start
	 * replaces any run still queued.
	 *
	 * @param int $before The lowest product ID done so far; 0 for none.
	 */
	public function next( int $before ): void {
		if ( 0 === $before ) {
			wp_unschedule_hook( $this->hook );
		}
		wp_schedule_single_event( time(), $this->hook, array( $before ) );
		spawn_cron();
	}

	/**
	 * Removes any queued run, whatever its argument (wp_clear_scheduled_hook( $hook ) would remove only events
	 * without one).
	 */
	public function cancel(): void {
		wp_unschedule_hook( $this->hook );
	}
}
