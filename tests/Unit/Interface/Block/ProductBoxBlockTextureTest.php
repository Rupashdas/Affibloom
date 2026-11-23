<?php
namespace Affibloom\Tests\Unit\Interface\Block;

use Affibloom\Interface\Block\ProductBoxBlock;
use Affibloom\Tests\Unit\TestCase;
use Brain\Monkey\Functions;

/**
 * Task 29A: Lasso's box was never texturized (its block drew through its shortcode, after wptexturize); Affibloom's
 * block, drawn before it, goes past it in a shortcode wptexturize skips, only inside the_content with do_shortcode to come.
 */
final class ProductBoxBlockTextureTest extends TestCase {

	private const BOX = "<div class=\"affibloom-box\"><a class=\"affibloom-button-1\">Buy Now Women's Shoe</a></div>";

	public function test_inside_the_content_the_box_goes_past_wptexturize(): void {
		Functions\when( 'doing_filter' )->justReturn( true );
		Functions\when( 'has_filter' )->justReturn( 11 );

		self::assertSame( '[affibloom_box_html]' . self::BOX . '[/affibloom_box_html]', ProductBoxBlock::past_texturize( self::BOX ) );
		self::assertSame( self::BOX, ProductBoxBlock::raw( array(), self::BOX ) );
		self::assertSame( array( 'code', 'affibloom_box_html' ), ProductBoxBlock::untextured( array( 'code' ) ) );
	}

	public function test_elsewhere_or_without_do_shortcode_the_box_is_returned_as_drawn(): void {
		Functions\when( 'doing_filter' )->justReturn( false );
		Functions\when( 'has_filter' )->justReturn( 11 );
		self::assertSame( self::BOX, ProductBoxBlock::past_texturize( self::BOX ) );

		Functions\when( 'doing_filter' )->justReturn( true );
		Functions\when( 'has_filter' )->justReturn( false );
		self::assertSame( self::BOX, ProductBoxBlock::past_texturize( self::BOX ) );
		self::assertSame( '', ProductBoxBlock::past_texturize( '' ) );
	}
}
