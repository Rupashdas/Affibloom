<?php
namespace Affibloom\Tests\Unit\Interface;

use Affibloom\Interface\ShortcodeText;
use PHPUnit\Framework\TestCase;

/**
 * The copied shortcodes (F31 R30, D9; Task 18S10).
 */
final class ShortcodeTextTest extends TestCase {

	/**
	 * A saved product's box by its ID; nothing for an unsaved one.
	 */
	public function test_a_products_shortcode(): void {
		self::assertSame( '[affibloom id="42"]', ShortcodeText::product( 42 ) );
		self::assertSame( '', ShortcodeText::product( 0 ) );
	}

	/**
	 * A group's grid by its slug (F31 R32, D9; Task 18S12); nothing without a slug.
	 */
	public function test_a_groups_shortcode(): void {
		self::assertSame( '[affibloom type="grid" group="best-shoes"]', ShortcodeText::group( 'best-shoes' ) );
		self::assertSame( '', ShortcodeText::group( '' ) );
	}
}
