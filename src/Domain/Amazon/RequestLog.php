<?php
/**
 * The site's record of its Creators API calls: the day's count, the last call, and Amazon's wait.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Amazon;

/**
 * The site's record of its Creators API calls (C1Q6a): how many item calls today (UTC), when the last call went out,
 * and until when Amazon asked to wait after a 429.
 */
final class RequestLog {

	/**
	 * Builds the record.
	 *
	 * @param string $day           The UTC day the count is for, "Y-m-d"; '' for none yet.
	 * @param int    $count         Item calls made that day.
	 * @param float  $last_at       When the last call went out, in seconds (Unix time).
	 * @param int    $blocked_until No call before this Unix time; 0 for none.
	 */
	public function __construct(
		public readonly string $day,
		public readonly int $count,
		public readonly float $last_at,
		public readonly int $blocked_until
	) {}

	/**
	 * The record for a day: the same one, or a new count for a new day.
	 *
	 * @param string $day A UTC day, "Y-m-d".
	 */
	public function on_day( string $day ): self {
		return $day === $this->day ? $this : new self( $day, 0, $this->last_at, $this->blocked_until );
	}

	/**
	 * The record after a call that went out now.
	 *
	 * @param float $now    The time, in seconds.
	 * @param bool  $counts Whether it counts against the day's quota (item calls do; token calls do not).
	 */
	public function called( float $now, bool $counts ): self {
		return new self( $this->day, $this->count + ( $counts ? 1 : 0 ), $now, $this->blocked_until );
	}

	/**
	 * The record with Amazon's wait.
	 *
	 * @param int $until No call before this Unix time.
	 */
	public function blocked( int $until ): self {
		return new self( $this->day, $this->count, $this->last_at, max( $this->blocked_until, $until ) );
	}
}
