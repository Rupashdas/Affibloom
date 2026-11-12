<?php
namespace Affibloom\Tests\Unit\Domain\Product;

use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\AffiliateUrl;
use PHPUnit\Framework\TestCase;

final class AffiliateUrlTest extends TestCase {
	public function test_a_tracking_link_is_kept_exactly_after_trimming(): void {
		$url = 'https://mizuno.pxf.io/c/123/456/789?u=https%3A%2F%2Fmizunousa.com%2Fwave&subId1=vv';

		self::assertSame( $url, AffiliateUrl::from_string( "  $url \n" )->value() );
	}

	public function test_an_empty_box_is_refused_as_empty(): void {
		$error = $this->refusal( "  \n" );

		self::assertSame( 'url', $error->field );
		self::assertSame( 'empty', $error->reason );
	}

	/**
	 * @dataProvider not_links
	 */
	public function test_what_is_not_an_http_link_is_refused( string $input ): void {
		self::assertSame( 'invalid_url', $this->refusal( $input )->reason );
	}

	/**
	 * What is still not an http(s) address after Lasso's repair (F18, decision D1).
	 *
	 * @return array<string, array{string}>
	 */
	public static function not_links(): array {
		return array(
			'words'          => array( 'not a link' ),
			'ftp'            => array( 'ftp://x.com/a' ),
			'javascript'     => array( 'javascript:alert(1)' ),
			'no host'        => array( 'https://' ),
			'space in host'  => array( 'https://exa mple.com/' ),
			// Lasso turns a host ending in .html into a relative path, which is not a link.
			'html file'      => array( 'example.html' ),
			'shortcode'      => array( '[lasso id="1"]' ),
			'mailto'         => array( 'mailto:shop@example.com' ),
		);
	}

	/**
	 * Lasso_Helper::add_https(): repaired, then kept.
	 *
	 * @dataProvider repairs
	 */
	public function test_lasso_repairs_what_it_is_given( string $input, string $stored ): void {
		self::assertSame( $stored, AffiliateUrl::from_string( $input )->value() );
	}

	/**
	 * @return array<string, array{string, string}>
	 */
	public static function repairs(): array {
		return array(
			'bare domain'         => array( 'mizunousa.com/wave', 'https://mizunousa.com/wave' ),
			'leading slashes'     => array( '//shop.example.org/x', 'https://shop.example.org/x' ),
			'doubled scheme'      => array( 'https://https://www.example.net/x', 'https://www.example.net/x' ),
			'http then https'     => array( 'http://https://www.example.net/x', 'https://www.example.net/x' ),
			'triple slash'        => array( 'https:///www.example.net/x', 'https://www.example.net/x' ),
			'stray bracket'       => array( ']https://www.example.net/x', 'https://www.example.net/x' ),
			'quoted shortcode'    => array( '[gift_item link=&quot;https://www.example.net/x', 'https://www.example.net/x' ),
			// Lasso would put https:// in front of a capitalised scheme; Affibloom reads the scheme without case.
			'capitalised scheme'  => array( 'HTTPS://WWW.Example.com/X', 'HTTPS://WWW.Example.com/X' ),
			'plain http kept'     => array( 'http://example.com/x', 'http://example.com/x' ),
		);
	}

	/**
	 * Lasso finds a product by its link ignoring case (MySQL's collation), one trailing slash, and &amp;.
	 */
	public function test_the_duplicate_key_ignores_case_a_trailing_slash_and_amp(): void {
		$key = AffiliateUrl::from_string( 'https://www.example.com/a?x=1&y=2' )->key();

		self::assertSame( 'https://www.example.com/a?x=1&y=2', $key );
		self::assertSame( $key, AffiliateUrl::from_string( 'HTTPS://WWW.Example.com/A?x=1&y=2/' )->key() );
		self::assertSame( $key, AffiliateUrl::from_string( 'https://www.example.com/a?x=1&amp;y=2' )->key() );
		self::assertSame( $key, AffiliateUrl::from_string( 'www.example.com/a?x=1&y=2' )->key() );
	}

	/**
	 * Lasso's get_title_by_url(): the label before the suffix, hyphens to spaces, words capitalised.
	 *
	 * @dataProvider site_names
	 */
	public function test_site_name_follows_lasso( string $url, string $name ): void {
		self::assertSame( $name, AffiliateUrl::from_string( $url )->site_name() );
	}

	/**
	 * @return array<string, array{string, string}>
	 */
	public static function site_names(): array {
		return array(
			'impact host'      => array( 'https://mizuno.pxf.io/c/1', 'Pxf' ),
			'www dropped'      => array( 'https://www.mizunousa.com/wave', 'Mizunousa' ),
			'hyphens'          => array( 'https://under-armour.com/x', 'Under Armour' ),
			// Lasso would say "Co"; the two-letter country suffix is the one improvement.
			'country suffix'   => array( 'https://www.example.co.uk/x', 'Example' ),
			'single label'     => array( 'http://localhost/x', 'Localhost' ),
		);
	}

	private function refusal( string $input ): InvalidField {
		try {
			AffiliateUrl::from_string( $input );
		} catch ( InvalidField $error ) {
			return $error;
		}
		self::fail( "Expected \"$input\" to be refused." );
	}
}
