<?php
namespace Affibloom\Tests\Unit\Domain\Opportunity;

use Affibloom\Domain\Opportunity\AmazonDomains;
use Affibloom\Domain\Opportunity\BaseDomain;
use PHPUnit\Framework\TestCase;

/**
 * Domains as opportunities compare them (F22 R8, D4, D5; Task O1).
 */
final class BaseDomainTest extends TestCase {

	public function test_the_host_in_lower_case_without_www(): void {
		self::assertSame( 'shop.example.com', BaseDomain::of( 'https://WWW.Shop.Example.com/a?b=1' ) );
		self::assertSame( 'example.com', BaseDomain::of( 'http://www.example.com' ) );
		self::assertSame( 'wwwexample.com', BaseDomain::of( 'https://wwwexample.com/' ) );
		self::assertSame( '', BaseDomain::of( '/relative/path' ) );
		self::assertSame( '', BaseDomain::of( '' ) );
	}

	/** F22 R71, R96: Lasso resolves an Amazon short link, so such a product is Amazon's (excluded), not every short link's. */
	public function test_a_product_on_an_amazon_short_link_is_amazons(): void {
		self::assertSame( 'amazon.com', BaseDomain::of_product( 'https://amzn.to/46fBGAJ' ) );
		self::assertSame( 'amazon.com', BaseDomain::of_product( 'https://www.amzn.com/dp/B00XOUQ8PE' ) );
		self::assertSame( 'shop.example.com', BaseDomain::of_product( 'https://www.shop.example.com/x' ) );
		self::assertSame( 'amazon.co.uk', BaseDomain::of_product( 'https://www.amazon.co.uk/dp/B00XOUQ8PE' ) );
	}

	public function test_lassos_twenty_one_amazon_domains_are_left_out_but_its_two_short_hosts_are_not(): void {
		self::assertCount( 21, AmazonDomains::EXCLUDED );
		self::assertTrue( AmazonDomains::excludes( 'amazon.co.uk' ) );
		self::assertTrue( AmazonDomains::excludes( 'smile.amazon.com' ) );
		self::assertFalse( AmazonDomains::excludes( 'amzn.to' ) );
		self::assertFalse( AmazonDomains::excludes( 'amzn.com' ) );
		self::assertFalse( AmazonDomains::excludes( 'shop.example.com' ) );
	}
}
