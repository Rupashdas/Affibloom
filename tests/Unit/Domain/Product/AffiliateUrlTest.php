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
	 * Task 11B adds Lasso's repair, which accepts "mizunousa.com/wave"; until then it is refused.
	 *
	 * @return array<string, array{string}>
	 */
	public static function not_links(): array {
		return array(
			'words'          => array( 'not a link' ),
			'no scheme'      => array( 'mizunousa.com/wave' ),
			'ftp'            => array( 'ftp://x.com/a' ),
			'javascript'     => array( 'javascript:alert(1)' ),
			'no host'        => array( 'https://' ),
			'space in host'  => array( 'https://exa mple.com/' ),
		);
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
