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
}
