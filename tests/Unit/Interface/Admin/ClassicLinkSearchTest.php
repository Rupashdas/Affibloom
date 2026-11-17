<?php
namespace Affibloom\Tests\Unit\Interface\Admin;

use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Interface\Admin\ClassicLinkSearch;
use Affibloom\Interface\PublicLinks;
use Affibloom\Tests\Unit\Fakes\InMemoryProductRepository;
use Affibloom\Tests\Unit\Fakes\InMemorySettingsRepository;
use Affibloom\Tests\Unit\TestCase;
use Brain\Monkey\Functions;

/**
 * F24 AC13 (Task CL9): the classic link dialog's search lists products after the posts, with their public link.
 */
final class ClassicLinkSearchTest extends TestCase {
	private InMemoryProductRepository $products;

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'home_url' )->justReturn( 'https://site.test' );
		Functions\when( 'esc_html' )->returnArg();
		Functions\when( '__' )->returnArg();
		Functions\when( 'wp_strip_all_tags' )->returnArg();
		Functions\when( 'current_user_can' )->justReturn( true );
		$this->products = new InMemoryProductRepository();
		$this->products->save( Product::draft( 'Mikasa Shoe', AffiliateUrl::from_string( 'https://www.example.com/shoe' ), Settings::from_array( array() ) )->with( array( 'slug' => 'mikasa-shoe' ) ) );
	}

	private function search(): ClassicLinkSearch {
		return new ClassicLinkSearch( $this->products, new PublicLinks( new InMemorySettingsRepository() ) );
	}

	public function test_a_search_lists_the_matching_products_after_the_posts(): void {
		$post    = array( 'ID' => 5, 'title' => 'Shoe guide', 'permalink' => 'https://site.test/shoe-guide/', 'info' => '2026/11/18' );
		$results = $this->search()->results( array( $post ), array( 's' => 'mikasa', 'offset' => 0 ) );

		self::assertCount( 2, $results );
		self::assertSame( $post, $results[0] );
		self::assertSame( array( 'Mikasa Shoe', 'https://site.test/mikasa-shoe/', 'Product' ), array( $results[1]['title'], $results[1]['permalink'], $results[1]['info'] ) );
		self::assertCount( 1, $this->search()->results( false, array( 's' => 'mikasa', 'offset' => 0 ) ) );
	}

	public function test_no_products_without_a_search_on_later_pages_or_for_a_reader(): void {
		self::assertFalse( $this->search()->results( false, array( 'offset' => 0 ) ) );
		self::assertFalse( $this->search()->results( false, array( 's' => 'mikasa', 'offset' => 20 ) ) );
		Functions\when( 'current_user_can' )->justReturn( false );
		self::assertFalse( $this->search()->results( false, array( 's' => 'mikasa', 'offset' => 0 ) ) );
	}
}
