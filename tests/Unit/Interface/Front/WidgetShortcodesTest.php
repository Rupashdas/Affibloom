<?php
namespace Affibloom\Tests\Unit\Interface\Front;

use Affibloom\Interface\Front\WidgetShortcodes;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * A text widget's boxes take the phone layout (F31 R16, A17, D11; Task 18S9).
 */
final class WidgetShortcodesTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * D11: `[affibloom …]` and `[affibloom]` are marked; Lasso's shortcodes (deviation 146), longer names and other text stay whole.
	 */
	public function test_only_the_box_shortcodes_are_marked(): void {
		self::assertSame(
			'[affibloom origin="widget" id="1"] [affibloom origin="widget"] [lasso id="2"] [lasso-attr id="3"] [affibloomx]',
			WidgetShortcodes::marked( '[affibloom id="1"] [affibloom] [lasso id="2"] [lasso-attr id="3"] [affibloomx]' )
		);
	}

	/**
	 * Lasso's rule: nothing while Elementor is active; a widget value that is not text passes through.
	 */
	public function test_elementor_keeps_the_text(): void {
		Functions\when( 'get_option' )->justReturn( array( 'elementor/elementor.php' ) );
		self::assertSame( '[affibloom id="1"]', ( new WidgetShortcodes() )->filter( '[affibloom id="1"]' ) );
		self::assertSame( array( 1 ), ( new WidgetShortcodes() )->filter( array( 1 ) ) );
	}

	public function test_without_elementor_the_text_is_marked(): void {
		Functions\when( 'get_option' )->justReturn( array( 'lasso/affiliate-plugin.php' ) );
		self::assertSame( '[affibloom origin="widget" id="1"]', ( new WidgetShortcodes() )->filter( '[affibloom id="1"]' ) );
	}
}
