<?php
namespace Affibloom\Tests\Unit\Application\Cloak;

use Affibloom\Application\Cloak\CloakResolver;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Tests\Unit\Fakes\InMemoryProductRepository;
use PHPUnit\Framework\TestCase;

/**
 * F24 AC7 (Task CL10): a renamed product's old slug answers, and a product that has the slug now comes first.
 */
final class CloakResolverTest extends TestCase {

	public function test_the_current_slug_first_then_an_old_one(): void {
		$repo    = new InMemoryProductRepository();
		$renamed = $repo->save( self::product( 'Aircat', 'aircat', array( 'aircat-volleyball-serving-machine' ) ) );
		$owner   = $repo->save( self::product( 'Old', 'old-name', array() ) );
		$repo->save( self::product( 'Other', 'other', array( 'old-name' ) ) );
		$resolver = new CloakResolver( $repo );

		self::assertSame( $renamed->id, $resolver->product_for( 'aircat-volleyball-serving-machine' )?->id );
		self::assertSame( $owner->id, $resolver->product_for( 'old-name' )?->id );
		self::assertNull( $resolver->product_for( 'nothing' ) );
	}

	/** @param list<string> $old */
	private static function product( string $name, string $slug, array $old ): Product {
		return Product::draft( $name, AffiliateUrl::from_string( 'https://www.example.com/' . $slug ), Settings::from_array( array() ) )->with(
			array(
				'slug'      => $slug,
				'old_slugs' => $old,
			)
		);
	}

	/** F27 R18 (Task V3): a permalink variant's key answers as its product. */
	public function test_a_variant_permalink_finds_its_product(): void {
		$repo     = new InMemoryProductRepository();
		$shoe     = $repo->save( self::product( 'Shoe', 'shoe', array() ) );
		$variants = new class() implements \Affibloom\Domain\Variant\VariantRepository {
			public function for_parent( int $product_id ): array {
				return array();
			}
			public function replace( int $product_id, array $variants ): array {
				return array();
			}
			public function parent_slug_for_permalink( string $key ): ?string {
				return 'shoe-ig' === $key ? 'shoe' : null;
			}
		};
		$resolver = new CloakResolver( $repo, $variants );

		self::assertSame( $shoe->id, $resolver->product_for_variant( 'shoe-ig' )?->id );
		self::assertNull( $resolver->product_for_variant( 'nothing' ) );
		self::assertNull( ( new CloakResolver( $repo ) )->product_for_variant( 'shoe-ig' ) );
	}
}
