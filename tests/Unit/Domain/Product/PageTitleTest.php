<?php
namespace Affibloom\Tests\Unit\Domain\Product;

use Affibloom\Domain\Product\PageTitle;
use PHPUnit\Framework\TestCase;

/**
 * Lasso_Helper::get_page_title() and the placeholder titles get_redirect_final_target() and the add throw away.
 */
final class PageTitleTest extends TestCase {
	public function test_the_title_is_read_and_cleaned(): void {
		$html = "<html><head><meta charset=\"utf-8\"><TITLE id=\"t\">  Mizuno Wave\n\t Momentum 3 &ndash; Men&#8217;s  </TITLE></head><title>second</title>";

		self::assertSame( 'Mizuno Wave Momentum 3 - Men’s', PageTitle::from_html( $html ) );
	}

	public function test_entities_are_decoded_and_tags_dropped(): void {
		self::assertSame( 'Bags & Backpacks', PageTitle::from_html( '<title>Bags <b>&amp;</b> Backpacks</title>' ) );
	}

	public function test_no_title_is_empty(): void {
		self::assertSame( '', PageTitle::from_html( '<html><body>No head here</body></html>' ) );
		self::assertSame( '', PageTitle::from_html( '<title>   </title>' ) );
	}

	public function test_a_long_title_is_cut_to_two_hundred_characters_with_dots(): void {
		$title = PageTitle::from_html( '<title>' . str_repeat( 'ab ', 100 ) . '</title>' );

		self::assertSame( 200, mb_strlen( $title ) );
		self::assertStringEndsWith( '...', $title );
	}

	/**
	 * @dataProvider placeholders
	 */
	public function test_placeholder_titles( string $title, bool $placeholder ): void {
		self::assertSame( $placeholder, PageTitle::is_placeholder( $title ) );
	}

	/**
	 * @return array<string, array{string, bool}>
	 */
	public static function placeholders(): array {
		return array(
			'cloudflare'      => array( 'Attention Required! | Cloudflare', true ),
			'just a moment'   => array( 'Just a moment...', true ),
			'please wait'     => array( 'Please Wait... | Shop', true ),
			'access denied'   => array( 'Access Denied', true ),
			'robot check'     => array( 'Robot Check', true ),
			'robot checkers'  => array( 'Robot Checkers for Sale', false ),
			'lasso default'   => array( 'Add a Link Title', true ),
			'a real title'    => array( 'Mizuno Wave Momentum 3', false ),
		);
	}
}
