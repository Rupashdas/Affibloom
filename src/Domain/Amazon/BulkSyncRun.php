<?php
/**
 * One bulk Amazon sync: the products still to sync, and how the others went.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Amazon;

/**
 * One bulk Amazon sync (C1 R43, D12; Lasso_Process_Bulk_Sync_Amazon): the products still to sync, how many there were,
 * the succeeded, skipped and failed counts, the failures (the newest 200, Lasso's MAX_FAILURE_ROWS) with why, and when
 * it started and finished.
 */
final class BulkSyncRun {

	/** Lasso's DEFAULT_BATCH_SIZE: at most 500 products a run. */
	public const LIMIT = 500;

	/** Lasso keeps the newest 200 failures. */
	public const MAX_FAILURES = 200;

	/** Lasso_Process::AGE_OUT: a run older than six hours no longer counts as running. */
	public const AGE_OUT_SECONDS = 6 * 3600;

	public const SUCCEEDED = 'succeeded';
	public const SKIPPED   = 'skipped';
	public const FAILED    = 'failed';

	/**
	 * Use start() or from_array().
	 *
	 * @param list<int>                                        $pending      Products not yet synced, in order.
	 * @param int                                              $total        Products in the run.
	 * @param array{succeeded: int, skipped: int, failed: int} $counts How the synced ones went.
	 * @param list<array{id: int, reason: string}>             $failures     The failed ones and why, oldest first.
	 * @param int                                              $started_at   Unix time the run started.
	 * @param string                                           $completed_at UTC time ('Y-m-d H:i:s') the last product was synced, or ''.
	 */
	private function __construct(
		public readonly array $pending,
		public readonly int $total,
		public readonly array $counts,
		public readonly array $failures,
		public readonly int $started_at,
		public readonly string $completed_at,
	) {}

	/**
	 * A new run for these products, the first 500 kept.
	 *
	 * @param list<int> $ids The pending products.
	 * @param int       $now Unix time.
	 */
	public static function start( array $ids, int $now ): self {
		$ids = array_slice( $ids, 0, self::LIMIT );

		return new self( $ids, count( $ids ), self::no_counts(), array(), $now, '' );
	}

	/**
	 * Whether products remain and the run is younger than the age-out.
	 *
	 * @param int $now Unix time.
	 */
	public function is_running( int $now ): bool {
		return array() !== $this->pending && $now - $this->started_at < self::AGE_OUT_SECONDS;
	}

	/**
	 * Share synced, 0-100 (Lasso's get_progress_percent).
	 */
	public function percent(): int {
		$done = $this->total - count( $this->pending );

		return 0 === $this->total ? 100 : min( 100, (int) round( $done / $this->total * 100 ) );
	}

	/**
	 * The next product to sync, or null when none remain.
	 */
	public function next(): ?int {
		return $this->pending[0] ?? null;
	}

	/**
	 * The run after the next product was synced; the last one stamps the run complete.
	 *
	 * @param string $kind   SUCCEEDED, SKIPPED or FAILED.
	 * @param string $reason Why it failed (kept for a failure only).
	 * @param string $now    UTC time, 'Y-m-d H:i:s'.
	 */
	public function advanced( string $kind, string $reason, string $now ): self {
		$id       = (int) $this->next();
		$pending  = array_slice( $this->pending, 1 );
		$counts   = $this->counts;
		$failures = $this->failures;

		$kind = isset( $counts[ $kind ] ) ? $kind : self::FAILED;
		++$counts[ $kind ];
		if ( self::FAILED === $kind ) {
			$failures = array_slice(
				array(
					...$failures,
					array(
						'id'     => $id,
						'reason' => $reason,
					),
				),
				-self::MAX_FAILURES
			);
		}

		return new self( $pending, $this->total, $counts, $failures, $this->started_at, array() === $pending ? $now : '' );
	}

	/**
	 * For storage.
	 *
	 * @return array{pending: list<int>, total: int, counts: array{succeeded: int, skipped: int, failed: int}, failures: list<array{id: int, reason: string}>, started_at: int, completed_at: string}
	 */
	public function to_array(): array {
		return array(
			'pending'      => $this->pending,
			'total'        => $this->total,
			'counts'       => $this->counts,
			'failures'     => $this->failures,
			'started_at'   => $this->started_at,
			'completed_at' => $this->completed_at,
		);
	}

	/**
	 * From storage, or null when the stored value is not a run.
	 *
	 * @param mixed $data What to_array() gave.
	 */
	public static function from_array( mixed $data ): ?self {
		if ( ! is_array( $data ) || ! isset( $data['pending'], $data['total'], $data['started_at'] )
			|| ! is_array( $data['pending'] ) || ! array_is_list( $data['pending'] ) ) {
			return null;
		}

		$counts = self::no_counts();
		foreach ( array_keys( $counts ) as $kind ) {
			$counts[ $kind ] = (int) ( $data['counts'][ $kind ] ?? 0 );
		}
		$failures = array();
		foreach ( is_array( $data['failures'] ?? null ) ? $data['failures'] : array() as $failure ) {
			if ( is_array( $failure ) ) {
				$failures[] = array(
					'id'     => (int) ( $failure['id'] ?? 0 ),
					'reason' => (string) ( $failure['reason'] ?? '' ),
				);
			}
		}

		return new self(
			array_map( 'intval', $data['pending'] ),
			(int) $data['total'],
			$counts,
			$failures,
			(int) $data['started_at'],
			(string) ( $data['completed_at'] ?? '' )
		);
	}

	/**
	 * Every count at zero.
	 *
	 * @return array{succeeded: int, skipped: int, failed: int}
	 */
	private static function no_counts(): array {
		return array(
			self::SUCCEEDED => 0,
			self::SKIPPED   => 0,
			self::FAILED    => 0,
		);
	}
}
