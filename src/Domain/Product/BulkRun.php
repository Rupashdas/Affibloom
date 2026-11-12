<?php
/**
 * One bulk add: the links still to add, how many there were, how many are done, and when it started.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Product;

/**
 * One bulk add (F18 section 8): the links still to add, how many there were, how many are done, and when it started.
 */
final class BulkRun {

	/** Lasso adds at most ten links at once (the dialog's counter and array_slice( $links, 0, 10 ) on the server). */
	public const LIMIT = 10;

	/** Lasso_Process::AGE_OUT: a run older than six hours no longer counts as running. */
	public const AGE_OUT_SECONDS = 6 * 3600;

	/**
	 * Use start() or from_array().
	 *
	 * @param list<string> $pending    Links not yet added, in order.
	 * @param int          $total      Links in the run.
	 * @param int          $done       Links handled so far, added or skipped.
	 * @param int          $started_at Unix time the run started.
	 */
	private function __construct(
		public readonly array $pending,
		public readonly int $total,
		public readonly int $done,
		public readonly int $started_at,
	) {}

	/**
	 * A new run from what was typed: lines trimmed, blank ones dropped, the first ten kept. Lasso keeps the first ten
	 * lines with the blanks among them; ten real links lose nothing it does.
	 *
	 * @param list<string> $lines One link per line.
	 * @param int          $now   Unix time.
	 */
	public static function start( array $lines, int $now ): self {
		$links = array_slice( array_values( array_filter( array_map( 'trim', $lines ), static fn( string $line ): bool => '' !== $line ) ), 0, self::LIMIT );

		return new self( $links, count( $links ), 0, $now );
	}

	/**
	 * Whether links remain and the run is younger than the age-out.
	 *
	 * @param int $now Unix time.
	 */
	public function is_running( int $now ): bool {
		return array() !== $this->pending && $now - $this->started_at < self::AGE_OUT_SECONDS;
	}

	/**
	 * Share handled, 0-100 (Lasso_Process_Bulk_Add_Links::get_progress()).
	 */
	public function percent(): int {
		return 0 === $this->total ? 100 : (int) round( $this->done / $this->total * 100 );
	}

	/**
	 * The next link to add, or null when none remain.
	 */
	public function next(): ?string {
		return $this->pending[0] ?? null;
	}

	/**
	 * The run after the next link has been handled.
	 */
	public function advanced(): self {
		return new self( array_slice( $this->pending, 1 ), $this->total, $this->done + 1, $this->started_at );
	}

	/**
	 * For storage.
	 *
	 * @return array{pending: list<string>, total: int, done: int, started_at: int}
	 */
	public function to_array(): array {
		return array(
			'pending'    => $this->pending,
			'total'      => $this->total,
			'done'       => $this->done,
			'started_at' => $this->started_at,
		);
	}

	/**
	 * From storage, or null when the stored value is not a run.
	 *
	 * @param mixed $data What to_array() gave.
	 */
	public static function from_array( mixed $data ): ?self {
		if ( ! is_array( $data ) || ! isset( $data['pending'], $data['total'], $data['done'], $data['started_at'] )
			|| ! is_array( $data['pending'] ) || ! array_is_list( $data['pending'] ) ) {
			return null;
		}

		return new self( array_map( 'strval', $data['pending'] ), (int) $data['total'], (int) $data['done'], (int) $data['started_at'] );
	}
}
