<?php
/**
 * The scheduled Amazon refresh: keeps stored Amazon prices and stock current.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Amazon;

use Affibloom\Application\Products\BackgroundJob;
use Affibloom\Domain\Amazon\DueAmazonData;
use Affibloom\Domain\Amazon\RefreshPolicy;
use Affibloom\Domain\Amazon\RefreshRun;
use Affibloom\Domain\Amazon\RefreshRunRepository;
use Affibloom\Domain\Settings\SettingsRepository;
use Closure;
use Throwable;

/**
 * The scheduled Amazon refresh (C1Q4b, C1E brought forward; Lasso's lasso_cron_update_amazon, R37-R39). Every hour,
 * while "Update Amazon pricing daily" is on and the credentials can be used, a run takes Lasso's batch of the due rows
 * (RefreshPolicy), oldest first, and refreshes them one a second in background passes. It writes Amazon's stored data
 * only (D7). A row Amazon has not found keeps its data and waits 24 hours; any other Amazon failure ends the run and
 * leaves every row as it was (the next hour tries again; C1Q6 adds the back-off).
 */
final class PriceRefreshService {

	/** Seconds one background pass may run, as bulk sync's (WP Background Processing's limit). */
	private const BUDGET_SECONDS = 20.0;

	/** One item a second, Amazon's rate (R15, R39). */
	private const PACING_SECONDS = 1;

	/** A run older than this is stale and replaced at the next hour (its passes stopped). */
	private const RUN_SECONDS = 3600;

	/**
	 * The clock, in seconds.
	 *
	 * @var Closure(): float
	 */
	private Closure $clock;

	/**
	 * Waits between two items.
	 *
	 * @var Closure(int): void
	 */
	private Closure $pause;

	/**
	 * Builds the service.
	 *
	 * @param DueAmazonData        $due      What is due.
	 * @param RefreshRunRepository $runs     The run in progress.
	 * @param BackgroundJob        $job      What runs process() later.
	 * @param AmazonDataService    $data     Amazon's data, one row at a time.
	 * @param SettingsRepository   $settings "Update Amazon pricing daily" and "Only update products with Locations".
	 * @param GuardedCatalog       $quota    Calls left today; the run keeps GuardedCatalog::RESERVE of them (C1Q6a).
	 * @param (Closure(): float)   $clock    Seconds now; microtime() unless a test gives another.
	 * @param (Closure(int): void) $pause    Waits that many seconds; sleep() unless a test gives another.
	 */
	public function __construct(
		private DueAmazonData $due,
		private RefreshRunRepository $runs,
		private BackgroundJob $job,
		private AmazonDataService $data,
		private SettingsRepository $settings,
		private GuardedCatalog $quota,
		?Closure $clock = null,
		?Closure $pause = null,
	) {
		$this->clock = $clock ?? static fn(): float => microtime( true );
		$this->pause = $pause ?? static function ( int $seconds ): void {
			sleep( $seconds );
		};
	}

	/**
	 * Whether the hourly event should be scheduled: "Update Amazon pricing daily" is on (R37).
	 */
	public function enabled(): bool {
		return $this->settings->get()->bool( 'update_pricing_daily' );
	}

	/**
	 * The hourly event: starts a run with Lasso's batch of the due rows and does its first pass. Nothing while switched
	 * off or without usable credentials, and nothing new while a run is still going.
	 */
	public function tick(): void {
		if ( ! $this->enabled() || ! $this->data->can_ask_amazon() ) {
			$this->runs->save( null );
			return;
		}

		$now = (int) ( $this->clock )();
		$run = $this->runs->get();
		if ( null !== $run && $now - $run->started < self::RUN_SECONDS ) {
			return;
		}

		$cutoff = RefreshPolicy::cutoff( $now );
		$room   = max( 0, $this->quota->left_today() - GuardedCatalog::RESERVE );
		// "Only update products with Locations" (C1 R38; Task C1E): only products used in content, when it is on.
		$used  = $this->settings->get()->bool( 'pricing_locations_only' );
		$queue = $this->due->due( $cutoff, min( $room, RefreshPolicy::batch( $this->due->count( $cutoff, $used ) ) ), $used );
		$this->runs->save( array() === $queue ? null : new RefreshRun( $queue, $now ) );
		$this->process();
	}

	/**
	 * One background pass: refreshes the run's rows one a second until none remain or the budget is spent, then wakes
	 * the next pass. An Amazon failure, or anything a row raises, ends the run.
	 */
	public function process(): void {
		$deadline = ( $this->clock )() + self::BUDGET_SECONDS;

		while ( true ) {
			$run  = $this->runs->get();
			$item = null === $run ? null : $run->next();
			if ( null === $run || null === $item ) {
				return;
			}

			try {
				// Ten rows of this store in one request (C1Q6b), when the next one was not fetched ahead.
				if ( ! $this->data->is_prefetched( $item['asin'], $item['country'] ) ) {
					$this->data->prefetch( array_slice( array_values( array_filter( $run->queue, static fn( array $row ): bool => $row['country'] === $item['country'] ) ), 0, 10 ) );
				}
				$done = $this->data->refresh_row( $item['asin'], $item['country'], $item['link'] );
			} catch ( Throwable $unexpected ) {
				unset( $unexpected );
				$done = false;
			}
			$run = $done ? $run->advanced() : null;
			$this->runs->save( $run );
			if ( null === $run ) {
				return;
			}

			( $this->pause )( self::PACING_SECONDS );
			if ( ( $this->clock )() >= $deadline ) {
				$this->job->dispatch();
				return;
			}
		}
	}
}
