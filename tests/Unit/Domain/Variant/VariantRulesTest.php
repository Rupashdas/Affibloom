<?php
namespace Affibloom\Tests\Unit\Domain\Variant;

use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Domain\Variant\SlugTaken;
use Affibloom\Domain\Variant\VariantKind;
use Affibloom\Domain\Variant\VariantRules;
use PHPUnit\Framework\TestCase;

/**
 * F27 R15 (Task V2): Lasso's refusals in Lasso's order, the first one winning, named under the row's key.
 */
final class VariantRulesTest extends TestCase {

	private static function product( ProductType $type = ProductType::Other ): Product {
		return Product::draft( 'Shoe', AffiliateUrl::from_string( 'https://www.example.com/shoe' ), Settings::from_array( array() ) )->with(
			array(
				'id'   => 7,
				'slug' => 'shoe',
				'type' => $type,
			)
		);
	}

	private static function taken( string $variant = '', string $post = '', string $product = '' ): SlugTaken {
		return new class( $variant, $post, $product ) implements SlugTaken {
			public function __construct( private string $variant, private string $post, private string $product ) {}
			public function by_other_variant( string $key, int $product_id ): bool {
				return $key === $this->variant;
			}
			public function by_post( string $key ): bool {
				return $key === $this->post;
			}
			public function by_other_product( string $key, int $product_id ): bool {
				return $key === $this->product;
			}
		};
	}

	/** @param list<array{name: string, key: string}> $rows */
	private static function refusal( array $rows, ?SlugTaken $taken = null, VariantKind $kind = VariantKind::Permalink ): ?string {
		try {
			VariantRules::check( $rows, self::product( VariantKind::AmazonTag === $kind ? ProductType::Amazon : ProductType::Other ), $kind, $taken ?? self::taken() );
		} catch ( InvalidField $refused ) {
			return $refused->field . ' ' . $refused->reason;
		}
		return null;
	}

	private static function row( string $key ): array {
		return array(
			'name' => 'A',
			'key'  => $key,
		);
	}

	public function test_each_refusal_and_its_row(): void {
		self::assertSame( 'variants.1.key variant_key_empty', self::refusal( array( self::row( 'a' ), self::row( '' ) ) ) );
		self::assertSame( 'variants.1.key variant_key_twice', self::refusal( array( self::row( 'a' ), self::row( 'a' ) ) ) );
		self::assertSame( 'variants.0.key variant_key_own_slug', self::refusal( array( self::row( 'shoe' ) ) ) );
		self::assertSame( 'variants.0.key variant_key_other_variant', self::refusal( array( self::row( 'x' ) ), self::taken( 'x' ) ) );
		self::assertSame( 'variants.0.key variant_key_post', self::refusal( array( self::row( 'about' ) ), self::taken( '', 'about' ) ) );
		self::assertSame( 'variants.0.key variant_key_product', self::refusal( array( self::row( 'aircat' ) ), self::taken( '', '', 'aircat' ) ) );
		self::assertNull( self::refusal( array( self::row( 'shoe-2' ), self::row( 'shoe-3' ) ) ) );
		self::assertNull( self::refusal( array() ) );
	}

	public function test_the_first_refusal_wins_in_lassos_order(): void {
		self::assertSame( 'variants.0.key variant_key_other_variant', self::refusal( array( self::row( 'x' ) ), self::taken( 'x', 'x', 'x' ) ) );
		self::assertSame( 'variants.0.key variant_key_post', self::refusal( array( self::row( 'x' ), self::row( '' ) ), self::taken( '', 'x' ) ) );
	}

	public function test_an_amazon_tag_is_unique_per_product_only(): void {
		self::assertNull( self::refusal( array( self::row( 'shoe' ) ), self::taken( 'shoe', 'shoe', 'shoe' ), VariantKind::AmazonTag ) );
		self::assertSame( 'variants.1.key variant_key_twice', self::refusal( array( self::row( 'a-20' ), self::row( 'a-20' ) ), null, VariantKind::AmazonTag ) );
	}
}
