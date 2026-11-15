<?php
/**
 * One scheduled Amazon refresh run: the rows it still has to refresh.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Amazon;

/**
 * One scheduled refresh run (C1Q4b): the due rows picked when the run started, in order, less the ones done. The
 * queue is fixed at the start, so a row that cannot be refreshed is not picked again in the same run.
 */
final class RefreshRun {

	/**
	 * Builds the run.
	 *
	 * @param list<array{asin: string, country: string, link: string}> $queue   The rows still to refresh, in order.
	 * @param int                                                      $started When it started, as a Unix timestamp.
	 */
	public function __construct( public readonly array $queue, public readonly int $started ) {}

	/**
	 * The next row, or null when the run is done.
	 *
	 * @return array{asin: string, country: string, link: string}|null
	 */
	public function next(): ?array {
		return $this->queue[0] ?? null;
	}

	/**
	 * The run after its next row, or null when that was the last.
	 */
	public function advanced(): ?self {
		$rest = array_slice( $this->queue, 1 );

		return array() === $rest ? null : new self( $rest, $this->started );
	}
}
