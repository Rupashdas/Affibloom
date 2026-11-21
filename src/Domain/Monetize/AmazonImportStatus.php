<?php
/**
 * Where the Amazon import stands.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Monetize;

/**
 * Lasso's import status payload (F35 R29, R30, R35; D19; Task AM4): the queue (unique Amazon products still to make),
 * the Dashboard's Amazon products, the index's placements, and the current batch. Complete is an empty queue with no
 * run going. A person's dismissal holds until the fingerprint changes: `pending_<queue>_<dashboard>` while there is
 * work, `complete_<dashboard>` after.
 */
final class AmazonImportStatus {

	/**
	 * Builds the status.
	 *
	 * @param int  $queue     Unique Amazon products (ASIN and store, or short link) still queued.
	 * @param int  $dashboard Published Amazon products.
	 * @param int  $indexed   Placements in the link index.
	 * @param bool $running   A run is going.
	 * @param int  $total     The current batch's size; 0 when unknown.
	 * @param int  $completed Links of the batch handled so far.
	 */
	public function __construct(
		public readonly int $queue,
		public readonly int $dashboard,
		public readonly int $indexed,
		public readonly bool $running = false,
		public readonly int $total = 0,
		public readonly int $completed = 0,
	) {}

	/**
	 * Whether the import has nothing left to do.
	 */
	public function complete(): bool {
		return 0 === $this->queue && ! $this->running;
	}

	/**
	 * Whether it has work (the notice shows, R30).
	 */
	public function busy(): bool {
		return ! $this->complete();
	}

	/**
	 * Whether the status is worth explaining outside the banner (R32, R34; Task AM5): while there is work, or in the first
	 * 24 hours after activation when the index has more placements than the Dashboard has links.
	 *
	 * @param bool $first_day Within 24 hours of activation.
	 */
	public function shows_context( bool $first_day ): bool {
		return $this->busy() || ( $first_day && $this->indexed > $this->dashboard );
	}

	/**
	 * The batch's progress in percent: 5 while running without a total (R29).
	 */
	public function percent(): int {
		if ( $this->total < 1 ) {
			return $this->running ? 5 : 0;
		}

		return (int) min( 100, round( 100 * $this->completed / $this->total ) );
	}

	/**
	 * What a dismissal is remembered against (R35).
	 */
	public function fingerprint(): string {
		return $this->complete() ? 'complete_' . $this->dashboard : 'pending_' . $this->queue . '_' . $this->dashboard;
	}

	/**
	 * The REST payload (R29).
	 *
	 * @return array{running: bool, queue_count: int, dashboard_count: int, links_indexed: int, complete: bool, batch_total: int, batch_completed: int, batch_percent: int}
	 */
	public function to_array(): array {
		return array(
			'running'         => $this->running,
			'queue_count'     => $this->queue,
			'dashboard_count' => $this->dashboard,
			'links_indexed'   => $this->indexed,
			'complete'        => $this->complete(),
			'batch_total'     => $this->total,
			'batch_completed' => $this->completed,
			'batch_percent'   => $this->percent(),
		);
	}
}
