<?php
/**
 * The click figures the screens show.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Clicks;

use Affibloom\Domain\Clicks\ClickStore;

/**
 * Lasso's fixed 30 days (F12 R4, D8; Tasks 34A-34C): today (UTC) and the 29 days before. The Dashboard's Clicks column
 * reads each product's figure.
 */
final class ClickReport {

	/** The days counted, today included (D8; U5 rejected). */
	public const DAYS = 30;

	/**
	 * Builds the report.
	 *
	 * @param ClickStore $clicks The counts.
	 */
	public function __construct( private ClickStore $clicks ) {}

	/**
	 * Each product's clicks in the last 30 days, 0 for none (the Dashboard's column, Task 34A).
	 *
	 * @param list<int> $product_ids The products.
	 * @param int       $now         Now, as a Unix time.
	 * @return array<int, int> By product ID, in the order asked.
	 */
	public function totals_for( array $product_ids, int $now ): array {
		$found  = array() === $product_ids ? array() : $this->clicks->totals_since( self::since( $now ), $product_ids );
		$totals = array();
		foreach ( $product_ids as $id ) {
			$totals[ $id ] = $found[ $id ] ?? 0;
		}

		return $totals;
	}

	/**
	 * The first day counted.
	 *
	 * @param int $now Now, as a Unix time.
	 */
	public static function since( int $now ): string {
		return gmdate( 'Y-m-d', $now - ( self::DAYS - 1 ) * 86400 );
	}
}
