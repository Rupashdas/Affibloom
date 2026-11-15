<?php
namespace Affibloom\Tests\Unit\Interface\Front;

use Affibloom\Interface\Front\BoxAssets;
use PHPUnit\Framework\TestCase;

/**
 * The page's colour and Custom CSS block (F5 R43, AC5; F16 AC10).
 */
final class BoxAssetsTest extends TestCase {

	public function test_the_root_block_then_the_custom_css_in_one_style_element(): void {
		self::assertSame(
			"<style type=\"text/css\">:root{--affibloom-main: #000;}\n.affibloom-title { color: red; }</style>\n",
			BoxAssets::head_style( ':root{--affibloom-main: #000;}', '.affibloom-title { color: red; }' )
		);
	}

	public function test_no_custom_css_prints_the_root_block_alone(): void {
		self::assertSame( "<style type=\"text/css\">:root{}</style>\n", BoxAssets::head_style( ':root{}', '' ) );
	}

	/**
	 * A value stored another way (an import) is cleaned again: it cannot close the style element or open a script.
	 */
	public function test_custom_css_cannot_close_the_style_element(): void {
		$html = BoxAssets::head_style( ':root{}', 'a{color:red}</style><script>alert(1)</script>' );

		self::assertSame( 1, substr_count( $html, '</style>' ) );
		self::assertStringNotContainsString( '<script', $html );
		self::assertStringContainsString( 'a{color:red}', $html );
	}
}
