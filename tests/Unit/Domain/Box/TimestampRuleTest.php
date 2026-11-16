<?php
namespace Affibloom\Tests\Unit\Domain\Box;

use Affibloom\Domain\Box\TimestampRule;
use PHPUnit\Framework\TestCase;

/**
 * The price timestamp (F5 R29, D6; AC22; Task 22C).
 */
final class TimestampRuleTest extends TestCase {

	/**
	 * Never on a basic link; on an Amazon product by the Timestamp element.
	 */
	public function test_it_shows_on_amazon_products_by_the_timestamp_element(): void {
		self::assertTrue( TimestampRule::shows( true, true ) );
		self::assertFalse( TimestampRule::shows( true, false ) );
		self::assertFalse( TimestampRule::shows( false, true ) );
	}

	/**
	 * Lasso's gmdate( 'm/d/Y h:i a T' ) of the stored UTC time, as its captured box shows it.
	 */
	public function test_the_last_update_prints_as_lasso_prints_it(): void {
		self::assertSame( '11/09/2026 12:01 am GMT', TimestampRule::text( '2026-11-09 00:01:00' ) );
		self::assertSame( '03/15/2026 01:30 pm GMT', TimestampRule::text( '2026-03-15 13:30:59' ) );
		self::assertSame( '', TimestampRule::text( '' ) );
		self::assertSame( '', TimestampRule::text( 'yesterday' ) );
	}
}
