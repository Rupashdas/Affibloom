<?php
namespace Affibloom\Tests\Unit\Application;

use Affibloom\Application\Products\FetchedPage;
use Affibloom\Application\Products\LinkResolver;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Tests\Unit\Fakes\FakePageFetcher;
use PHPUnit\Framework\TestCase;

/**
 * Lasso's naming path: get_redirect_final_target( $url, true, true ) without its cloud.
 */
final class LinkResolverTest extends TestCase {
	/**
	 * @param array<string, ?FetchedPage> $pages URL => page (null: no answer).
	 */
	private function fetcher( array $pages = array() ): FakePageFetcher {
		return new FakePageFetcher( $pages );
	}

	public function test_an_embedded_destination_names_it_without_a_request(): void {
		$fetcher  = $this->fetcher();
		$resolved = ( new LinkResolver( $fetcher ) )->resolve( AffiliateUrl::from_string( 'https://mizuno.pxf.io/c/1/2/3?u=https%3A%2F%2Fmizunousa.com%2Fwave' ) );

		self::assertSame( 'Mizunousa', $resolved->title );
		self::assertSame( 'https://mizunousa.com/wave', $resolved->url );
		self::assertSame( array(), $fetcher->asked );
	}

	public function test_amazon_is_never_requested(): void {
		$fetcher  = $this->fetcher();
		$resolved = ( new LinkResolver( $fetcher ) )->resolve( AffiliateUrl::from_string( 'https://www.amazon.com/dp/B0CPH9NPK7?tag=vbvault-20' ) );

		self::assertSame( 'Amazon', $resolved->title );
		self::assertSame( array(), $fetcher->asked );
	}

	public function test_the_page_title_after_redirects_names_it(): void {
		$fetcher  = $this->fetcher( array( 'https://go.example/x' => new FetchedPage( 200, 'https://www.shop.example/p', '<title>Volleyball Knee Pads</title>' ) ) );
		$resolved = ( new LinkResolver( $fetcher ) )->resolve( AffiliateUrl::from_string( 'https://go.example/x' ) );

		self::assertSame( 'Volleyball Knee Pads', $resolved->title );
		self::assertSame( 'https://www.shop.example/p', $resolved->url );
	}

	public function test_a_page_that_is_not_found_still_gives_its_title_as_in_lasso(): void {
		$fetcher = $this->fetcher( array( 'https://shop.example/gone' => new FetchedPage( 404, 'https://shop.example/gone', '<title>Page not found</title>' ) ) );

		self::assertSame( 'Page not found', ( new LinkResolver( $fetcher ) )->resolve( AffiliateUrl::from_string( 'https://shop.example/gone' ) )->title );
	}

	/**
	 * @dataProvider no_answers
	 */
	public function test_no_answer_or_a_403_falls_back_to_the_links_site( ?FetchedPage $page ): void {
		$fetcher  = $this->fetcher( array( 'https://www.under-armour.example/p' => $page ) );
		$resolved = ( new LinkResolver( $fetcher ) )->resolve( AffiliateUrl::from_string( 'https://www.under-armour.example/p' ) );

		self::assertSame( 'Under Armour', $resolved->title );
		self::assertSame( 'https://www.under-armour.example/p', $resolved->url );
	}

	/**
	 * @return array<string, array{?FetchedPage}>
	 */
	public static function no_answers(): array {
		return array(
			'no answer' => array( null ),
			'403'       => array( new FetchedPage( 403, 'https://www.under-armour.example/p', '<title>Forbidden</title>' ) ),
		);
	}

	public function test_a_placeholder_title_gives_the_final_sites_name(): void {
		$fetcher = $this->fetcher( array( 'https://go.example/x' => new FetchedPage( 200, 'https://www.rogue-fitness.example/p', '<title>Just a moment...</title>' ) ) );

		self::assertSame( 'Rogue Fitness', ( new LinkResolver( $fetcher ) )->resolve( AffiliateUrl::from_string( 'https://go.example/x' ) )->title );
	}

	public function test_no_title_gives_the_final_sites_name(): void {
		$fetcher = $this->fetcher( array( 'https://go.example/x' => new FetchedPage( 200, 'https://www.walmart.example/p', '<p>no title</p>' ) ) );

		self::assertSame( 'Walmart', ( new LinkResolver( $fetcher ) )->resolve( AffiliateUrl::from_string( 'https://go.example/x' ) )->title );
	}

	public function test_a_destination_embedded_in_the_final_url_names_it(): void {
		$fetcher  = $this->fetcher( array( 'https://short.example/a' => new FetchedPage( 200, 'https://track.example/c?u=https%3A%2F%2Fusa.mizuno.example%2Fshoe', '<title>Redirecting</title>' ) ) );
		$resolved = ( new LinkResolver( $fetcher ) )->resolve( AffiliateUrl::from_string( 'https://short.example/a' ) );

		self::assertSame( 'Mizuno', $resolved->title );
		self::assertSame( 'https://usa.mizuno.example/shoe', $resolved->url );
	}

	public function test_a_redirect_into_amazon_is_named_amazon(): void {
		// The fetcher stops before an Amazon host and reports where it would have gone, with no body.
		$fetcher  = $this->fetcher( array( 'https://geni.us/abc' => new FetchedPage( 302, 'https://www.amazon.com/dp/B0CPH9NPK7?tag=vbvault-20', '' ) ) );
		$resolved = ( new LinkResolver( $fetcher ) )->resolve( AffiliateUrl::from_string( 'https://geni.us/abc' ) );

		self::assertSame( 'Amazon', $resolved->title );
		self::assertSame( 'https://www.amazon.com/dp/B0CPH9NPK7?tag=vbvault-20', $resolved->url );
	}
}
