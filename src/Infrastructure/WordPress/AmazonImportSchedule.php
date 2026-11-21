<?php
/**
 * When the Amazon import runs.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

/**
 * The Amazon import's WP-Cron event (F35 D11, D23; Task AM1): `affibloom_amazon_import` every five minutes while
 * Auto-Monetization is on (its own interval, `affibloom_five_minutes`), none while it is off. AM3's switch starts and
 * stops it.
 */
final class AmazonImportSchedule {

	/** The event. */
	public const HOOK = 'affibloom_amazon_import';

	/** Its interval. */
	public const INTERVAL = 'affibloom_five_minutes';

	/**
	 * Adds the five-minute interval (`cron_schedules`).
	 *
	 * @param mixed $schedules WordPress's intervals.
	 * @return mixed
	 */
	public static function interval( mixed $schedules ): mixed {
		if ( is_array( $schedules ) ) {
			$schedules[ self::INTERVAL ] = array(
				'interval' => 5 * MINUTE_IN_SECONDS,
				'display'  => __( 'Every five minutes', 'affibloom' ),
			);
		}

		return $schedules;
	}

	/**
	 * Schedules the import, unless it already is.
	 */
	public function start(): void {
		if ( false === wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, self::INTERVAL, self::HOOK );
		}
	}

	/**
	 * Schedules it with its first run now (switched on, D18; Task AM3).
	 */
	public function start_now(): void {
		$this->stop();
		wp_schedule_event( time(), self::INTERVAL, self::HOOK );
	}

	/**
	 * Unschedules it.
	 */
	public function stop(): void {
		wp_clear_scheduled_hook( self::HOOK );
	}
}
