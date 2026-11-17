<?php
/**
 * Where a background rebuild of the link index stands.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\LinkIndex;

/**
 * The rebuild's state (F21 D11, analysis "Data": option `affibloom_link_index_state`): idle or running, a full or daily
 * run, how many hosts are done of how many, when it started and finished, and the last finished run's figures (the
 * Link Index card's "Last Updated", Task 9J).
 */
final class RebuildState {

	public const IDLE    = 'idle';
	public const RUNNING = 'running';
	public const FULL    = 'full';

	/**
	 * Describes the state.
	 *
	 * @param string                                     $status      IDLE or RUNNING.
	 * @param string                                     $run         FULL.
	 * @param int                                        $done        Hosts indexed in this run.
	 * @param int                                        $total       Hosts in this run.
	 * @param string                                     $started_at  When it started, UTC.
	 * @param string                                     $finished_at When the last run finished, UTC; '' never.
	 * @param array{hosts: int, rows: int, seconds: int} $last        The last finished run's figures.
	 * @param int                                        $rows        Rows written in this run so far.
	 */
	public function __construct(
		public readonly string $status = self::IDLE,
		public readonly string $run = self::FULL,
		public readonly int $done = 0,
		public readonly int $total = 0,
		public readonly string $started_at = '',
		public readonly string $finished_at = '',
		public readonly array $last = array(
			'hosts'   => 0,
			'rows'    => 0,
			'seconds' => 0,
		),
		public readonly int $rows = 0,
	) {}

	/**
	 * Whether a rebuild is under way.
	 */
	public function running(): bool {
		return self::RUNNING === $this->status;
	}

	/**
	 * A full run starting now over this many hosts.
	 *
	 * @param int    $total How many hosts.
	 * @param string $now   Now, UTC.
	 */
	public function started( int $total, string $now ): self {
		return new self( self::RUNNING, self::FULL, 0, $total, $now, $this->finished_at, $this->last, 0 );
	}

	/**
	 * The run after a batch of this many hosts and rows.
	 *
	 * @param int $hosts Hosts indexed in the batch.
	 * @param int $rows  Rows they gave.
	 */
	public function advanced( int $hosts, int $rows ): self {
		return new self( $this->status, $this->run, min( $this->total, $this->done + $hosts ), $this->total, $this->started_at, $this->finished_at, $this->last, $this->rows + $rows );
	}

	/**
	 * The run finished now: idle, with its figures as the last run's.
	 *
	 * @param string $now     Now, UTC.
	 * @param int    $seconds How long it took.
	 */
	public function finished( string $now, int $seconds ): self {
		return new self(
			self::IDLE,
			$this->run,
			$this->done,
			$this->total,
			$this->started_at,
			$now,
			array(
				'hosts'   => $this->done,
				'rows'    => $this->rows,
				'seconds' => max( 0, $seconds ),
			),
			$this->rows
		);
	}

	/**
	 * The state as stored.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return get_object_vars( $this );
	}

	/**
	 * The state from what was stored; anything missing or of the wrong kind is the idle state's.
	 *
	 * @param mixed $stored The option's value.
	 */
	public static function from_array( mixed $stored ): self {
		if ( ! is_array( $stored ) ) {
			return new self();
		}
		$int  = static fn( string $key ): int => is_numeric( $stored[ $key ] ?? null ) ? (int) $stored[ $key ] : 0;
		$text = static fn( string $key ): string => is_string( $stored[ $key ] ?? null ) ? $stored[ $key ] : '';
		$last = is_array( $stored['last'] ?? null ) ? $stored['last'] : array();

		return new self(
			self::RUNNING === $text( 'status' ) ? self::RUNNING : self::IDLE,
			self::FULL,
			$int( 'done' ),
			$int( 'total' ),
			$text( 'started_at' ),
			$text( 'finished_at' ),
			array(
				'hosts'   => (int) ( $last['hosts'] ?? 0 ),
				'rows'    => (int) ( $last['rows'] ?? 0 ),
				'seconds' => (int) ( $last['seconds'] ?? 0 ),
			),
			$int( 'rows' )
		);
	}
}
