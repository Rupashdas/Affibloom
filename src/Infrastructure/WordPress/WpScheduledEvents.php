<?php
/**
 * WP-Cron's scheduled events.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Application\Jobs\ScheduledEvents;

/**
 * WP-Cron's scheduled events (Task 32D), read from _get_cron_array() and run as wp-cron.php runs them: a recurring
 * event is rescheduled, a single one removed, then its hook fires with its arguments.
 */
final class WpScheduledEvents implements ScheduledEvents {

	/**
	 * The events of these hooks due by the given time, the oldest first.
	 *
	 * @param list<string> $hooks The hooks.
	 * @param int          $now   Unix time.
	 * @return list<array{hook: string, time: int, args: array<int, mixed>}>
	 */
	public function due( array $hooks, int $now ): array {
		$crons = _get_cron_array();
		$due   = array();
		foreach ( $crons as $time => $by_hook ) {
			if ( (int) $time > $now ) {
				break; // WP-Cron keeps the array sorted by time.
			}
			foreach ( array_intersect_key( $by_hook, array_flip( $hooks ) ) as $hook => $events ) {
				foreach ( $events as $event ) {
					$due[] = array(
						'hook' => (string) $hook,
						'time' => (int) $time,
						'args' => array_values( $event['args'] ),
					);
				}
			}
		}

		return $due;
	}

	/**
	 * Runs one event now, as WP-Cron would.
	 *
	 * @param array{hook: string, time: int, args: array<int, mixed>} $event The event.
	 */
	public function run( array $event ): void {
		$scheduled = wp_get_scheduled_event( $event['hook'], $event['args'], $event['time'] );
		if ( false === $scheduled ) {
			return; // Another request ran it meanwhile.
		}
		if ( false !== $scheduled->schedule ) {
			wp_reschedule_event( $event['time'], $scheduled->schedule, $event['hook'], $event['args'] );
		}
		wp_unschedule_event( $event['time'], $event['hook'], $event['args'] );
		do_action_ref_array( $event['hook'], $event['args'] ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- only Affibloom's own hooks (DueJobs passes CronRegistry's).
	}
}
