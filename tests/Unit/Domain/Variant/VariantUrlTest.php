<?php
namespace Affibloom\Tests\Unit\Domain\Variant;

use Affibloom\Domain\Variant\VariantKind;
use Affibloom\Domain\Variant\VariantUrl;
use PHPUnit\Framework\TestCase;

/**
 * F27 R9 (Task V1): a permalink variant at the site's root, an Amazon variant as the destination with its tag.
 */
final class VariantUrlTest extends TestCase {

	public function test_a_permalink_variant_is_at_the_root_whatever_the_prefix(): void {
		self::assertSame( 'https://site.test/shoe-ig/', VariantUrl::of( VariantKind::Permalink, 'shoe-ig', 'https://site.test/', 'https://www.example.com/shoe' ) );
	}

	public function test_an_amazon_variant_replaces_or_adds_the_tag(): void {
		$tag = static fn( string $destination ): string => VariantUrl::of( VariantKind::AmazonTag, 'insta-20', 'https://site.test', $destination );

		self::assertSame( 'https://www.amazon.com/dp/B01?th=1&tag=insta-20&psc=1', $tag( 'https://www.amazon.com/dp/B01?th=1&tag=vbvault-20&psc=1' ) );
		self::assertSame( 'https://www.amazon.com/dp/B01?tag=insta-20', $tag( 'https://www.amazon.com/dp/B01' ) );
		self::assertSame( 'https://www.amazon.com/dp/B01?th=1&tag=insta-20#reviews', $tag( 'https://www.amazon.com/dp/B01?th=1#reviews' ) );
		self::assertSame( 'https://www.amazon.com/dp/B01?tag=a%20b', VariantUrl::of( VariantKind::AmazonTag, 'a b', '', 'https://www.amazon.com/dp/B01?tag=x' ) );
	}

	public function test_an_empty_key_gives_the_destination(): void {
		self::assertSame( 'https://www.example.com/shoe', VariantUrl::of( VariantKind::Permalink, '', 'https://site.test', 'https://www.example.com/shoe' ) );
	}
}
