<?php
namespace Affibloom\Tests\Unit\Application;

use Affibloom\Application\Settings\LinkDefaultsService;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Tests\Unit\Fakes\InMemoryProductRepository;
use PHPUnit\Framework\TestCase;

final class LinkDefaultsServiceTest extends TestCase {
	public function test_a_switch_is_set_on_every_product(): void {
		$repo = new InMemoryProductRepository();
		$repo->save( Product::draft( 'One', AffiliateUrl::from_string( 'https://a.example/1' ), Settings::from_array( array() ) ) );
		$repo->save( Product::draft( 'Two', AffiliateUrl::from_string( 'https://a.example/2' ), Settings::from_array( array() ) ) );

		$count = ( new LinkDefaultsService( $repo ) )->apply_to_all( 'new_tab', false );

		self::assertSame( 2, $count );
		foreach ( $repo->saved as $product ) {
			self::assertFalse( $product->new_tab );
			self::assertTrue( $product->nofollow );
		}
	}

	public function test_an_unknown_key_is_refused(): void {
		$this->expectException( \InvalidArgumentException::class );
		( new LinkDefaultsService( new InMemoryProductRepository() ) )->apply_to_all( 'link_cloaking', true );
	}
}
