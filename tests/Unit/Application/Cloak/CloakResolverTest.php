<?php
namespace Affibloom\Tests\Unit\Application\Cloak;

use Affibloom\Application\Cloak\CloakResolver;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Tests\Unit\Fakes\InMemoryProductRepository;
use Affibloom\Tests\Unit\Fakes\InMemoryVariantRepository;
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

	/**
	 * F24 D3, the user's decision of 2026-11-24 (deviation 165): a retired product's address sends readers to the
	 * published product it points at, as Lasso's repoint; with no pointer, or a pointer to nothing published, to its own.
	 */
	public function test_a_retired_product_sends_readers_to_the_product_it_points_at(): void {
		$repo     = new InMemoryProductRepository();
		$live     = $repo->save( self::product( 'Live', 'live', array() ) );
		$old      = $repo->save( self::product( 'Old', 'old', array() ) );
		$alone    = $repo->save( self::product( 'Alone', 'alone', array() ) );
		$resolver = new CloakResolver( $repo );
		$repo->retire( $old, (int) $live->id );
		$repo->retire( $alone, 0 );

		self::assertSame( $live->id, $resolver->sends_to( $resolver->product_for( 'old' ) )->id );
		self::assertSame( $alone->id, $resolver->sends_to( $resolver->product_for( 'alone' ) )->id, 'No pointer: its own.' );
		self::assertSame( $live->id, $resolver->sends_to( $live )->id, 'A published product is its own.' );

		$repo->trash( (int) $live->id );
		self::assertSame( $old->id, $resolver->sends_to( $resolver->product_for( 'old' ) )->id, 'The target is gone: its own.' );
	}

	/** F27 R18 (Task V3): a permalink variant's key answers as its product. */
	public function test_a_variant_permalink_finds_its_product(): void {
		$repo     = new InMemoryProductRepository();
		$shoe     = $repo->save( self::product( 'Shoe', 'shoe', array() ) );
		$variants = new InMemoryVariantRepository( array( 'shoe-ig' => 'shoe' ) );
		$resolver = new CloakResolver( $repo, $variants );

		self::assertSame( $shoe->id, $resolver->product_for_variant( 'shoe-ig' )?->id );
		self::assertNull( $resolver->product_for_variant( 'nothing' ) );
		self::assertNull( ( new CloakResolver( $repo ) )->product_for_variant( 'shoe-ig' ) );
	}
}
