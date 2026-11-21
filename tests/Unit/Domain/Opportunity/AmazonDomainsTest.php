<?php
namespace Affibloom\Tests\Unit\Domain\Opportunity;

use Affibloom\Domain\Opportunity\AmazonDomains;
use PHPUnit\Framework\TestCase;

/**
 * Amazon's domains in F22 (R8, R60, R64; Tasks O1, O9, O10).
 */
final class AmazonDomainsTest extends TestCase {

	public function test_lassos_monetize_test_for_an_amazon_address(): void {
		self::assertTrue( AmazonDomains::in_url( 'https://www.amazon.com/dp/B0TEST' ) );
		self::assertTrue( AmazonDomains::in_url( 'https://amazon.co.uk/dp/B0TEST' ) );
		self::assertTrue( AmazonDomains::in_url( 'https://amzn.to/3abc' ) );
		self::assertFalse( AmazonDomains::in_url( 'https://shop.example/amazonia' ) );
	}

	public function test_all_holds_lassos_23_and_the_list_leaves_out_its_short_link_hosts(): void {
		self::assertCount( 23, AmazonDomains::ALL );
		self::assertFalse( AmazonDomains::excludes( 'amzn.to' ) );
		self::assertTrue( AmazonDomains::excludes( 'amazon.de' ) );
	}
}
