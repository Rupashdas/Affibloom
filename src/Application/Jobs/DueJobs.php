<?php
/**
 * Running due Affibloom jobs from an admin page where WP-Cron cannot.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Jobs;

use Affibloom\Domain\Settings\SettingsRepository;

/**
 * Lasso's lasso_check_crons fallback (F11 R34, D22; Task 32D): where WP-Cron cannot spawn, an admin page's poll runs
 * the due events of Affibloom's own hooks inline, the oldest first, at most "Maximum cronjobs at a time" (1-3, Lasso's
 * clamp) per call. Other plugins' events are left to WP-Cron.
 */
final class DueJobs {

	/**
	 * Builds the runner.
	 *
	 * @param ScheduledEvents    $events   WP-Cron's events.
	 * @param list<string>       $hooks    Affibloom's hooks (CronRegistry).
	 * @param SettingsRepository $settings The manual limit.
	 */
	public function __construct( private ScheduledEvents $events, private array $hooks, private SettingsRepository $settings ) {}

	/**
	 * Runs the due events, up to the limit.
	 *
	 * @param int $now Unix time.
	 * @return list<string> The hooks that ran.
	 */
	public function run_due( int $now ): array {
		$limit = max( 1, min( 3, $this->settings->get()->int( 'manual_process_limit' ) ) );
		$ran   = array();
		foreach ( array_slice( $this->events->due( $this->hooks, $now ), 0, $limit ) as $event ) {
			$this->events->run( $event );
			$ran[] = $event['hook'];
		}

		return $ran;
	}
}
