<?php
namespace Affibloom\Tests\Unit\Domain\Amazon;

use Affibloom\Domain\Amazon\Availability;
use PHPUnit\Framework\TestCase;

/**
 * Lasso's extract_quantity() (C1 R12): known types decide, then the message, else in stock.
 */
final class AvailabilityTest extends TestCase {
	public function test_out_of_stock_types(): void {
		foreach ( array( 'OUT_OF_STOCK', 'UNAVAILABLE', 'unknown' ) as $type ) {
			self::assertFalse( Availability::from_listing( $type, 'In stock soon' )->in_stock, $type );
		}
	}

	public function test_in_stock_types_win_over_the_message(): void {
		foreach ( array( 'IN_STOCK', 'IN_STOCK_SCARCE', 'AVAILABLE_DATE', 'LEADTIME', 'PREORDER' ) as $type ) {
			self::assertTrue( Availability::from_listing( $type, 'Temporarily out of stock' )->in_stock, $type );
		}
	}

	public function test_an_unknown_type_reads_the_message(): void {
		self::assertFalse( Availability::from_listing( 'OTHER', 'Temporarily OUT of stock.' )->in_stock );
		self::assertFalse( Availability::from_listing( '', 'Currently unavailable.' )->in_stock );
		self::assertTrue( Availability::from_listing( '', 'Usually ships in 2 days' )->in_stock );
	}

	public function test_no_listing_is_out_of_stock(): void {
		self::assertFalse( Availability::none()->in_stock );
	}
}
