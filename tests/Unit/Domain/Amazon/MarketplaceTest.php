<?php
namespace Affibloom\Tests\Unit\Domain\Amazon;

use Affibloom\Domain\Amazon\Marketplace;
use PHPUnit\Framework\TestCase;

final class MarketplaceTest extends TestCase {
	public function test_the_select_lists_lassos_eighteen_stores_in_its_order(): void {
		$names = Marketplace::names();
		self::assertCount( 18, $names );
		self::assertSame( array( 'us', 'au', 'br' ), array_slice( array_keys( $names ), 0, 3 ) );
		self::assertSame( 'United Kingdom', $names['gb'] );
		self::assertSame( 'United Arab Emirates', $names['ae'] );
	}

	public function test_a_store_is_asked_by_its_marketplace_host(): void {
		self::assertSame( 'www.amazon.co.uk', Marketplace::host( 'gb' ) );
		self::assertSame( 'www.amazon.co.jp', Marketplace::host( 'jp' ) );
	}

	public function test_an_unknown_code_falls_back_to_the_us_store(): void {
		self::assertFalse( Marketplace::is_country( 'uk' ) );
		self::assertSame( 'www.amazon.com', Marketplace::host( 'uk' ) );
		self::assertTrue( Marketplace::is_country( 'us' ) );
	}
}
