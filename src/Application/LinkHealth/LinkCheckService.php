<?php
/**
 * The daily link check: its pass, its batches, and the check of one product.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\LinkHealth;

use Affibloom\Application\Products\BackgroundJob;
use Affibloom\Domain\LinkHealth\CheckPass;
use Affibloom\Domain\LinkHealth\CheckPassRepository;
use Affibloom\Domain\LinkHealth\CheckResult;
use Affibloom\Domain\LinkHealth\IssueType;
use Affibloom\Domain\LinkHealth\LinkHealthPolicy;
use Affibloom\Domain\LinkHealth\LinkIssue;
use Affibloom\Domain\LinkHealth\LinkIssueRepository;
use Affibloom\Domain\Settings\SettingsRepository;
use Closure;
use DateTimeImmutable;

/**
 * F11's daily check (R3, R6, R9, R12, R13; D2, D4-D6, D10, D11; Lasso's check_issue()). Once a site day the pass
 * queues every covered product in Lasso's order; each batch checks up to 100 of them within 10 seconds of work (the
 * time is checked between requests) and chains the next batch while any are left. A first failure is checked again an
 * hour later: once the queue is empty, a batch checks the failures that are an hour old, and when some are younger it
 * waits for the oldest. Every confirmed issue fires the detected listener (`affibloom_link_issue_detected`).
 */
final class LinkCheckService {

	/** At most this many products a batch (R3: Lasso's LIMIT 100). */
	public const BATCH_SIZE = 100;

	/** Seconds of work a batch does before it hands over to the next (R7). */
	public const BUDGET = 10.0;

	/** Seconds between a first failure and its second check (D4). */
	private const CONFIRM_AFTER = 3600;

	/**
	 * Builds the service.
	 *
	 * @param LinkIssueRepository                $issues   The rows and the covered products.
	 * @param CheckPassRepository                $passes   The daily pass.
	 * @param LinkStatusChecker                  $checker  Asks a link for its status.
	 * @param BackgroundJob                      $job      Schedules the next batch.
	 * @param SettingsRepository                 $settings "Locations only" (R5).
	 * @param Closure(): DateTimeImmutable       $now      The time now.
	 * @param Closure(int, string, string): void $detected Called with product, URL and code for every confirmed issue.
	 */
	public function __construct(
		private LinkIssueRepository $issues,
		private CheckPassRepository $passes,
		private LinkStatusChecker $checker,
		private BackgroundJob $job,
		private SettingsRepository $settings,
		private Closure $now,
		private Closure $detected,
	) {}

	/**
	 * Starts the day's pass: rows of deleted products go, every covered product is queued and the first batch is
	 * scheduled. Nothing happens when the day's pass already ran.
	 *
	 * @param string $day The site day (Y-m-d).
	 * @return bool Whether a pass started.
	 */
	public function start_pass( string $day ): bool {
		if ( $this->passes->get()->date === $day ) {
			return false;
		}
		$this->issues->delete_orphans();
		$queue = $this->issues->due( $this->locations_only() );
		$this->passes->save( new CheckPass( $day, count( $queue ), 0, $queue ) );
		$this->job->dispatch();

		return true;
	}

	/**
	 * Runs one batch: the queue first, then the second checks that are due; schedules the next batch.
	 *
	 * @param int   $limit  At most this many products.
	 * @param float $budget Seconds of work; the next request is not started after it (the first always is).
	 */
	public function run_batch( int $limit = self::BATCH_SIZE, float $budget = self::BUDGET ): void {
		$started = microtime( true );
		$pass    = $this->passes->get();
		$queue   = $pass->queue;
		$checked = 0;
		$done    = 0;

		while ( array() !== $queue && $done < $limit && ( 0 === $done || microtime( true ) - $started < $budget ) ) {
			$this->check_product( (int) array_shift( $queue ) );
			++$checked;
			++$done;
		}
		$this->passes->save( $pass->advanced( $queue, $checked ) );
		if ( array() !== $queue ) {
			$this->job->dispatch();
			return;
		}

		$room    = $limit - $done;
		$due     = $room > 0 ? $this->issues->confirmations( $this->now()->modify( '-' . self::CONFIRM_AFTER . ' seconds' ), $this->locations_only(), $room ) : array();
		$changed = 0;
		$tried   = 0;
		foreach ( $due as $product_id ) {
			if ( $done > 0 && microtime( true ) - $started >= $budget ) {
				break;
			}
			$changed += null === $this->check_product( $product_id ) ? 0 : 1;
			++$tried;
			++$done;
		}
		// More may be due when this batch stopped short (time, or its room filled), but only if it got anywhere:
		// due second checks whose requests were not sent come back unchanged and must not chain batches.
		$this->schedule_confirmations( $changed > 0 && ( $tried < count( $due ) || count( $due ) === $room ) );
	}

	/**
	 * Checks one covered product now and stores the result.
	 *
	 * @param int $product_id The product.
	 * @return LinkIssue|null Its row afterwards; null when it is not covered or nothing changed.
	 */
	public function check_product( int $product_id ): ?LinkIssue {
		$url = $this->issues->destination( $product_id );
		if ( null === $url ) {
			return null;
		}
		$url    = trim( $url );
		$result = '' === $url ? CheckResult::certain( IssueType::NOT_FOUND ) : $this->checker->status( $url );
		$row    = LinkHealthPolicy::apply( $this->issues->get( $product_id ), $product_id, $url, $result, $this->now() );
		if ( null === $row ) {
			return null;
		}
		$this->issues->save( $row );
		if ( $row->is_open() ) {
			( $this->detected )( $product_id, $url, $row->issue_type );
		}

		return $row;
	}

	/**
	 * Schedules the batch that runs the next second check: now when this batch stopped short of them, else when the
	 * oldest waiting one is an hour old. A second check that is already due but did not change (its request was not
	 * sent) waits for tomorrow's pass, so a blocked link cannot keep batches running.
	 *
	 * @param bool $stopped_short Whether this batch left due second checks unchecked (limit or time).
	 */
	private function schedule_confirmations( bool $stopped_short ): void {
		if ( $stopped_short ) {
			$this->job->dispatch();
			return;
		}
		$oldest = $this->issues->next_confirmation( $this->locations_only() );
		if ( null === $oldest ) {
			return;
		}
		$wait = $oldest->getTimestamp() + self::CONFIRM_AFTER - $this->now()->getTimestamp();
		if ( $wait > 0 ) {
			$this->job->dispatch( $wait );
		}
	}

	/**
	 * Whether only products used in content are checked (R5; "locations only", on by default).
	 */
	private function locations_only(): bool {
		return $this->settings->get()->bool( 'pricing_locations_only' );
	}

	/**
	 * The time now.
	 */
	private function now(): DateTimeImmutable {
		return ( $this->now )();
	}
}
