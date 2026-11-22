<?php
/**
 * The daily link check's WP-Cron events.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Cron;

use Affibloom\Application\Jobs\CpuGuard;
use Affibloom\Application\Jobs\JobSlots;
use Affibloom\Application\LinkHealth\LinkCheckService;
use Affibloom\Application\Products\BackgroundJob;
use Affibloom\Application\Upgrade\UpgradeLock;
use Affibloom\Domain\LinkHealth\LinkHealthPolicy;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Module;
use DateTimeImmutable;

/**
 * F11 D2 (ADR-4; Lasso's lasso_cron_check_issue): `affibloom_daily` at 03:00 site time starts the day's pass, and the
 * chained `affibloom_link_check_batch` single events run it, one at a time behind a 60-second lock. A daily event
 * another plugin cleared is scheduled again on the next `init` (edge case 7). `affibloom_link_check_product` [id] is
 * the check a product save starts (D12, Task 30B); a first failure schedules the same event for its second check an
 * hour later (D4). Settings → General (Task 32C, D21): a busy server or two Affibloom batches already running make a
 * batch wait a minute; a new Cron interval moves the waiting batch.
 */
final class LinkCheckCron implements Module {

	public const DAILY   = 'affibloom_daily';
	public const BATCH   = 'affibloom_link_check_batch';
	public const PRODUCT = 'affibloom_link_check_product';

	/**
	 * Every hook this module schedules, for the CronRegistry deactivation clears.
	 *
	 * @var list<string>
	 */
	public const HOOKS = array( self::DAILY, self::BATCH, self::PRODUCT );

	/**
	 * Builds the module.
	 *
	 * @param LinkCheckService $checks The check.
	 * @param UpgradeLock      $lock   One batch at a time.
	 * @param CpuGuard         $cpu    Whether the server is too busy.
	 * @param JobSlots         $slots  Processes Execute Limit.
	 * @param BackgroundJob    $job    Schedules the batch again.
	 */
	public function __construct( private LinkCheckService $checks, private UpgradeLock $lock, private CpuGuard $cpu, private JobSlots $slots, private BackgroundJob $job ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( self::DAILY, array( $this, 'start' ) );
		add_action( self::BATCH, array( $this, 'batch' ) );
		add_action( self::PRODUCT, array( $this, 'product' ) );
		add_action( 'init', array( $this, 'schedule_daily' ) );
		add_action( 'admin_init', array( $this, 'catch_up' ) );
		add_action( 'update_option_affibloom_settings', array( $this, 'interval_changed' ), 10, 2 );
	}

	/**
	 * Starts the day's pass (site time), unless it already ran today; a pass of today that lost its batch gets it back.
	 */
	public function start(): void {
		if ( ! $this->checks->start_pass( (string) wp_date( 'Y-m-d' ) ) ) {
			$this->checks->resume( false !== wp_next_scheduled( self::BATCH ) );
		}
	}

	/**
	 * An administrator's page load gives a pass that lost its batch its next one (exit check finding, 2026-11-22), as the
	 * link index's catch-up does for a rebuild. Only for someone who manages products: WordPress also runs admin_init
	 * for a visitor's admin-ajax.php and admin-post.php (37A, S4).
	 */
	public function catch_up(): void {
		if ( ! current_user_can( Capabilities::PRODUCTS ) ) {
			return;
		}
		$this->checks->resume( false !== wp_next_scheduled( self::BATCH ) );
	}

	/**
	 * Runs one batch, unless another is running.
	 */
	public function batch(): void {
		if ( ! $this->lock->acquire() ) {
			return;
		}
		try {
			if ( $this->cpu->busy() || ! $this->slots->run( array( $this->checks, 'run_batch' ) ) ) {
				$this->job->dispatch( CpuGuard::BUSY_WAIT );
			}
		} finally {
			$this->lock->release();
		}
	}

	/**
	 * Checks one product; a first failure gets its second check an hour later.
	 *
	 * @param int|string $product_id The product, as the event's argument.
	 */
	public function product( $product_id ): void {
		$row = $this->checks->check_product( (int) $product_id );
		if ( null !== $row && $row->failures > 0 ) {
			wp_schedule_single_event( time() + LinkHealthPolicy::CONFIRM_AFTER, self::PRODUCT, array( (int) $product_id ) );
		}
	}

	/**
	 * A new Cron interval moves a batch that waits for the old one (R33: saving reschedules).
	 *
	 * @param mixed $stored The settings before.
	 * @param mixed $saved  The settings after.
	 */
	public function interval_changed( $stored, $saved ): void {
		$before = is_array( $stored ) ? (int) ( $stored['cron_interval'] ?? 0 ) : 0;
		$after  = is_array( $saved ) ? (int) ( $saved['cron_interval'] ?? 0 ) : 0;
		$next   = wp_next_scheduled( self::BATCH );
		if ( $before === $after || false === $next || $next <= time() ) {
			return;
		}
		wp_unschedule_event( $next, self::BATCH );
		$this->job->dispatch( max( 0, min( 3, $after ) ) * HOUR_IN_SECONDS );
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
