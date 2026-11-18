<?php
/**
 * The scheduled events WP-Cron holds.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Jobs;

/**
 * The scheduled events WP-Cron holds (Task 32D): the due ones of some hooks, and running one inline as WP-Cron would.
 */
interface ScheduledEvents {

	/**
	 * The events of these hooks due by the given time, the oldest first.
	 *
	 * @param list<string> $hooks The hooks.
	 * @param int          $now   Unix time.
	 * @return list<array{hook: string, time: int, args: array<int, mixed>}>
	 */
	public function due( array $hooks, int $now ): array;

	/**
	 * Runs one event now, as WP-Cron would: a recurring one is scheduled again, a single one removed, then its hook
	 * fires.
	 *
	 * @param array{hook: string, time: int, args: array<int, mixed>} $event The event.
	 */
	public function run( array $event ): void;
}
