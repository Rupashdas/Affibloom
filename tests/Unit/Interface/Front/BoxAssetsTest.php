<?php
namespace Affibloom\Tests\Unit\Interface\Front;

use Affibloom\Interface\Front\BoxAssets;
use PHPUnit\Framework\TestCase;

/**
 * The page's colour and Custom CSS block (F5 R43, AC5; F16 AC10) and the Font Awesome config (R49).
 */
final class BoxAssetsTest extends TestCase {

	public function test_the_root_block_then_the_custom_css_after_a_line_break(): void {
		self::assertSame(
			":root{--affibloom-main: #000;}\n.affibloom-title { color: red; }",
			BoxAssets::head_style( ':root{--affibloom-main: #000;}', '.affibloom-title { color: red; }' )
		);
	}

	public function test_no_custom_css_leaves_the_root_block_alone(): void {
		self::assertSame( ':root{}', BoxAssets::head_style( ':root{}', '' ) );
	}

	/**
	 * A value stored another way (an import) is cleaned again: it cannot close the style element or open a script.
	 */
	public function test_custom_css_cannot_close_the_style_element(): void {
		$css = BoxAssets::head_style( ':root{}', 'a{color:red}</style><script>alert(1)</script>' );

		self::assertStringNotContainsString( '<', $css );
		self::assertStringContainsString( 'a{color:red}', $css );
	}

	/**
	 * AMP pages take the boxes' CSS inline (F4 R75, Task ED4): every theme and display type is styled, and nothing AMP
	 * refuses is in it.
	 */
	public function test_the_amp_stylesheet_styles_every_theme_and_display_type(): void {
		$css = (string) file_get_contents( dirname( __DIR__, 4 ) . '/assets/front/box-amp.css' );
		$css = (string) preg_replace( '#/\*.*?\*/#s', '', $css );

		foreach ( array( 'cactus', 'cutter', 'flow', 'geek', 'lab', 'llama', 'money', 'splash', 'grid-row', 'list', 'fields-pros', 'stars', 'image-container' ) as $part ) {
			self::assertStringContainsString( '.affibloom-' . $part . ' ', $css, $part );
		}
		self::assertStringNotContainsString( '!important', $css );
		self::assertStringNotContainsString( 'lasso', $css );
		self::assertDoesNotMatchRegularExpression( '#url\(\'(?!images/)#', $css );
	}

	public function test_the_font_awesome_config_follows_the_setting(): void {
		self::assertSame( "<script type=\"text/javascript\">window.FontAwesomeConfig = { autoReplaceSvg: true }</script>\n", BoxAssets::font_awesome_config( true ) );
		self::assertStringContainsString( 'autoReplaceSvg: false }', BoxAssets::font_awesome_config( false ) );
	}
}
