<?php
/**
 * The site's record of its Creators API calls in one option.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Domain\Amazon\RequestLedger;
use Affibloom\Domain\Amazon\RequestLog;

/**
 * The record of Creators API calls (C1Q6a) in the option `affibloom_amazon_rate`, not autoloaded (read only around an
 * Amazon call).
 */
final class OptionRequestLedger implements RequestLedger {

	public const OPTION = 'affibloom_amazon_rate';

	/**
	 * The record; an empty one when there is none.
	 */
	public function get(): RequestLog {
		$stored = get_option( self::OPTION, array() );
		$stored = is_array( $stored ) ? $stored : array();

		return new RequestLog(
			is_string( $stored['day'] ?? null ) ? $stored['day'] : '',
			is_numeric( $stored['count'] ?? null ) ? (int) $stored['count'] : 0,
			is_numeric( $stored['last_at'] ?? null ) ? (float) $stored['last_at'] : 0.0,
			is_numeric( $stored['blocked_until'] ?? null ) ? (int) $stored['blocked_until'] : 0
		);
	}

	/**
	 * Keeps the record.
	 *
	 * @param RequestLog $log The record.
	 */
	public function save( RequestLog $log ): void {
		update_option(
			self::OPTION,
			array(
				'day'           => $log->day,
				'count'         => $log->count,
				'last_at'       => $log->last_at,
				'blocked_until' => $log->blocked_until,
			),
			false
		);
	}
}
