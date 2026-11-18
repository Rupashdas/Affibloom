<?php
namespace Affibloom\Tests\Unit\Interface\Front;

use Affibloom\Application\LinkIndex\RenderedLinks;
use Affibloom\Domain\LinkIndex\LinkLocation;
use Affibloom\Domain\LinkIndex\LocationKey;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Interface\Front\LinkAttributesFilter;
use Affibloom\Interface\PublicLinks;
use Affibloom\Tests\Unit\Fakes\InMemoryLinkLocations;
use Affibloom\Tests\Unit\Fakes\InMemoryProductRepository;
use Affibloom\Tests\Unit\Fakes\InMemorySettingsRepository;
use Affibloom\Tests\Unit\TestCase;
use Brain\Monkey\Functions;

/**
 * F21 D13 (Tasks 35H, 35E): a printed post's links matched to their rows, in "content" and in the places it shows
 * (a recipe), each with its own count of the same URL.
 */
final class LinkAttributesFilterTest extends TestCase {

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		$includes = dirname( __DIR__, 7 ) . '/wp-includes/';
		if ( ! class_exists( \WP_HTML_Tag_Processor::class, false ) ) {
			require_once $includes . 'class-wp-token-map.php';
			foreach ( array( 'html5-named-character-references', 'class-wp-html-attribute-token', 'class-wp-html-span', 'class-wp-html-text-replacement', 'class-wp-html-decoder', 'class-wp-html-tag-processor' ) as $file ) {
				require_once $includes . 'html-api/' . $file . '.php';
			}
		}
	}

	private static function row( string $source, string $url, int $occurrence, int $product ): LinkLocation {
		return new LinkLocation( host_id: 5, host_url: '', source: $source, occurrence: $occurrence, product_id: $product, link_type: 'product', display_type: 'Text', url: $url, url_md5: LocationKey::md5( $url ) );
	}

	public function test_links_are_matched_in_content_first_then_in_the_places_the_post_shows(): void {
		Functions\when( 'get_the_ID' )->justReturn( 5 );
		Functions\when( 'home_url' )->justReturn( 'https://site.test' );
		// What the tag processor calls of WordPress when it writes an attribute.
		Functions\when( 'wp_has_noncharacters' )->justReturn( false );
		Functions\when( 'esc_attr' )->alias( 'htmlspecialchars' );
		Functions\when( 'wp_kses_uri_attributes' )->justReturn( array( 'href', 'src' ) );
		Functions\when( 'esc_url' )->returnArg();
		$products = new InMemoryProductRepository();
		$shoe     = $products->save( Product::draft( 'Shoe', AffiliateUrl::from_string( 'https://www.example.com/shoe' ), Settings::from_array( array() ) ) );
		$url      = 'https://old.example/shoe';
		$rows     = new InMemoryLinkLocations();
		// The URL once in content (untied) and once in a recipe (tied).
		$rows->replace_host( 5, array( self::row( 'content', $url, 1, 0 ), self::row( 'wprm:12', $url, 1, (int) $shoe->id ) ) );
		$filter = new LinkAttributesFilter( new RenderedLinks( $rows, $products ), new PublicLinks( new InMemorySettingsRepository() ) );

		$html = $filter->content( '<p><a href="' . $url . '">In text</a></p><div class="recipe"><a href="' . $url . '">In recipe</a></div>' );

		self::assertStringContainsString( '<a href="' . $url . '">In text</a>', $html );
		self::assertMatchesRegularExpression( '#<a [^>]*data-affibloom-id="' . $shoe->id . '"[^>]*href="https://site.test/shoe/"[^>]*>In recipe</a>#', $html );
	}
}
