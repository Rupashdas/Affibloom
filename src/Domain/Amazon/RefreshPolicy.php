<?php
/**
 * Lasso's rules for the scheduled Amazon refresh.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Amazon;

/**
 * Lasso's rules for the scheduled Amazon refresh (C1 R37, R38; C1Q4b): data is due after 24 hours, and a run takes a
 * twentieth of what is due, at least 100 and at most 500, never more than is due.
 */
final class RefreshPolicy {

	/** Hours before stored Amazon data is due again (R38). */
	public const MAX_AGE_HOURS = 24;

	/**
	 * How many due rows one run refreshes (R37: ceil( due / 20 ) clamped to 100-500).
	 *
	 * @param int $due How many rows are due.
	 */
	public static function batch( int $due ): int {
		return min( $due, max( 100, min( 500, (int) ceil( $due / 20 ) ) ) );
	}

	/**
	 * Data stored before this time ("Y-m-d H:i:s", UTC) is due.
	 *
	 * @param int $now The time now, as a Unix timestamp.
	 */
	public static function cutoff( int $now ): string {
		return gmdate( 'Y-m-d H:i:s', $now - self::MAX_AGE_HOURS * 3600 );
	}
}
