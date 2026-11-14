<?php
namespace Affibloom\Tests\Unit\Domain\Product;

use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\PlainUrlKey;
use PHPUnit\Framework\TestCase;

final class PlainUrlKeyTest extends TestCase {
	private function key( string $url ): string {
		return PlainUrlKey::of( AffiliateUrl::from_string( $url ) );
	}

	public function test_two_links_to_one_page_share_a_key_whatever_their_query(): void {
		self::assertSame( 'https://merchant.example/p', $this->key( 'https://merchant.example/p?ref=a' ) );
		self::assertSame( $this->key( 'https://merchant.example/p?ref=a' ), $this->key( 'https://Merchant.example/p/?ref=b#top' ) );
	}

	public function test_different_pages_have_different_keys(): void {
		self::assertNotSame( $this->key( 'https://merchant.example/p1?x=1' ), $this->key( 'https://merchant.example/p2?x=1' ) );
	}

	public function test_lassos_exceptions_keep_the_query(): void {
		self::assertSame( 'https://www.brooksbrothers.com/item?id=5', $this->key( 'https://www.brooksbrothers.com/item?id=5' ) );
		self::assertSame( 'https://mechanicalkeyboards.com/shop?sku=9', $this->key( 'https://mechanicalkeyboards.com/shop?sku=9' ) );
	}

	public function test_an_amazon_link_has_no_plain_key(): void {
		self::assertSame( '', $this->key( 'https://www.amazon.com/dp/B000000001?tag=x-20' ) );
		self::assertSame( '', $this->key( 'https://www.amazon.com/s?k=volleyball' ) );
	}
}
