<?php
/**
 * The daily link check's WP-Cron events.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Cron;

use Affibloom\Application\LinkHealth\LinkCheckService;
use Affibloom\Application\Upgrade\UpgradeLock;
use Affibloom\Module;
use DateTimeImmutable;

/**
 * F11 D2 (ADR-4; Lasso's lasso_cron_check_issue): `affibloom_daily` at 03:00 site time starts the day's pass, and the
 * chained `affibloom_link_check_batch` single events run it, one at a time behind a 60-second lock. A daily event
 * another plugin cleared is scheduled again on the next `init` (edge case 7).
 */
final class LinkCheckCron implements Module {

	public const DAILY = 'affibloom_daily';
	public const BATCH = 'affibloom_link_check_batch';

	/**
	 * Every hook this module schedules, for the CronRegistry deactivation clears.
	 *
	 * @var list<string>
	 */
	public const HOOKS = array( self::DAILY, self::BATCH );

	/**
	 * Builds the module.
	 *
	 * @param LinkCheckService $checks The check.
	 * @param UpgradeLock      $lock   One batch at a time.
	 */
	public function __construct( private LinkCheckService $checks, private UpgradeLock $lock ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( self::DAILY, array( $this, 'start' ) );
		add_action( self::BATCH, array( $this, 'batch' ) );
		add_action( 'init', array( $this, 'schedule_daily' ) );
	}

	/**
	 * Starts the day's pass (site time), unless it already ran today.
	 */
	public function start(): void {
		$this->checks->start_pass( (string) wp_date( 'Y-m-d' ) );
	}

	/**
	 * Runs one batch, unless another is running.
	 */
	public function batch(): void {
		if ( ! $this->lock->acquire() ) {
			return;
		}
		try {
			$this->checks->run_batch();
		} finally {
			$this->lock->release();
		}
	}

	/**
	 * Schedules the daily event at the next 03:00 site time, once.
	 */
	public function schedule_daily(): void {
		if ( false !== wp_next_scheduled( self::DAILY ) ) {
			return;
		}
		$at = new DateTimeImmutable( 'today 03:00', wp_timezone() );
		if ( $at->getTimestamp() <= time() ) {
			$at = $at->modify( '+1 day' );
		}
		wp_schedule_event( $at->getTimestamp(), 'daily', self::DAILY );
	}
}
