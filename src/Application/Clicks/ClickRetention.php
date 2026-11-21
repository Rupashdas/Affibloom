<?php
/**
 * How long click rows are kept.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Clicks;

use Affibloom\Domain\Clicks\ClickStore;

/**
 * ADR-005's retention (F12 AC8; Task 33C): a day's rows are kept 400 days, today counting as day 0, and removed by the
 * daily job after that.
 */
final class ClickRetention {

	/** How many days back a row is kept. */
	public const DAYS = 400;

	/**
	 * Builds the retention.
	 *
	 * @param ClickStore $clicks The counts.
	 */
	public function __construct( private ClickStore $clicks ) {}

	/**
	 * Removes the rows of days older than 400 days.
	 *
	 * @param int $now Now, as a Unix time.
	 */
	public function run( int $now ): void {
		$this->clicks->delete_before( gmdate( 'Y-m-d', $now - self::DAYS * 86400 ) );
	}
}
