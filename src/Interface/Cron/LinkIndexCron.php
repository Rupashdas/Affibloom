<?php
/**
 * The link index's background rebuild and daily pass.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Cron;

use Affibloom\Application\LinkIndex\LinkIndexRebuild;
use Affibloom\Application\Products\BackgroundJob;
use Affibloom\Application\Upgrade\UpgradeLock;
use Affibloom\Domain\LinkIndex\RebuildStateRepository;
use Affibloom\Domain\Settings\SettingsRepository;
use Affibloom\Application\Jobs\CpuGuard;
use Affibloom\Application\Jobs\JobSlots;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Module;

/**
 * F21 R14-R18, R21, D5, D11, D12 (ADR-4): the rebuild's batches (one WP-Cron single event at a time, each scheduling
 * the next, behind a 60-second lock), waiting a minute while the server is busier than the CPU threshold; the daily
 * pass, which also restarts a rebuild whose chain broke; and, where WP-Cron is disabled, an admin page load that runs
 * at most "Maximum cronjob at a time" due batches itself. `affibloom_link_index_start` starts a full rebuild.
 */
final class LinkIndexCron implements Module {

	public const BATCH = 'affibloom_link_index_batch';
	public const DAILY = 'affibloom_link_index_daily';
	public const START = 'affibloom_link_index_start';

	/**
	 * Every hook this module schedules, for the CronRegistry deactivation clears.
	 *
	 * @var list<string>
	 */
	public const HOOKS = array( self::BATCH, self::DAILY, self::START );

	/**
	 * Builds the module.
	 *
	 * @param LinkIndexRebuild       $rebuild  The rebuild.
	 * @param RebuildStateRepository $state    Where it stands.
	 * @param UpgradeLock            $lock     One batch at a time.
	 * @param BackgroundJob          $job      Schedules a batch.
	 * @param CpuGuard               $cpu      Whether the server is too busy (Task 32C).
	 * @param SettingsRepository     $settings The catch-up limit.
	 * @param JobSlots               $slots    Processes Execute Limit (Task 32C).
	 */
	public function __construct(
		private LinkIndexRebuild $rebuild,
		private RebuildStateRepository $state,
		private UpgradeLock $lock,
		private BackgroundJob $job,
		private CpuGuard $cpu,
		private SettingsRepository $settings,
		private JobSlots $slots,
	) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( self::BATCH, array( $this, 'batch' ) );
		add_action( self::DAILY, array( $this, 'daily' ) );
		add_action( self::START, array( $this, 'start' ) );
		add_action( 'init', array( $this, 'schedule_daily' ) );
		add_action( 'admin_init', array( $this, 'catch_up' ) );
	}

	/**
	 * Starts a full rebuild.
	 */
	public function start(): void {
		$this->rebuild->start();
	}

	/**
	 * Runs one batch, unless another is running or the server is busy: then it waits a minute. A batch that finds the
	 * lock taken goes back on the schedule, so the chain is never dropped (the 2026-11-21 stall: an admin page took the
	 * due batch while another page still ran one; 35F).
	 */
	public function batch(): void {
		if ( ! $this->lock->acquire() ) {
			$this->job->dispatch( CpuGuard::BUSY_WAIT );
			return;
		}
		try {
			if ( $this->cpu->busy() || ! $this->slots->run( array( $this->rebuild, 'next_batch' ) ) ) {
				$this->job->dispatch( CpuGuard::BUSY_WAIT );
			}
		} finally {
			$this->lock->release();
		}
	}

	/**
	 * The daily pass; a running rebuild with no batch waiting (its chain broke) gets one.
	 */
	public function daily(): void {
		$this->rebuild->daily();
		if ( $this->state->get()->running() && false === wp_next_scheduled( self::BATCH ) ) {
			$this->job->dispatch();
		}
	}

	/**
	 * Schedules the daily pass once.
	 */
	public function schedule_daily(): void {
		if ( false === wp_next_scheduled( self::DAILY ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::DAILY );
		}
	}

	/**
	 * Where WP-Cron is disabled, an admin page runs up to "Maximum cronjob at a time" (1-3) due batches itself (D12),
	 * after restarting a broken chain. Only for a user with Affibloom's capability: admin_init also runs for a
	 * visitor's admin-ajax.php and admin-post.php requests, which must not start work (Task 37A, finding S4).
	 */
	public function catch_up(): void {
		if ( ! current_user_can( Capabilities::PRODUCTS ) ) {
			return;
		}
		if ( ! defined( 'DISABLE_WP_CRON' ) || ! DISABLE_WP_CRON ) {
			return;
		}
		// A running rebuild with no batch waiting lost its chain (a request that died mid-batch): it gets one back. Only
		// one batch is ever queued, so this cannot start a second chain.
		if ( $this->state->get()->running() && false === wp_next_scheduled( self::BATCH ) ) {
			$this->job->dispatch();
		}
		$limit = max( 1, min( 3, $this->settings->get()->int( 'manual_process_limit' ) ) );
		for ( $run = 0; $run < $limit; $run++ ) {
			$due = wp_next_scheduled( self::BATCH );
			if ( false === $due || $due > time() ) {
				return;
			}
			wp_unschedule_event( $due, self::BATCH );
			$this->batch();
		}
	}
}
