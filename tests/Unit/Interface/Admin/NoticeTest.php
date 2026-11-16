<?php
namespace Affibloom\Tests\Unit\Interface\Admin;

use Affibloom\Interface\Admin\Notice;
use Affibloom\Tests\Unit\TestCase;
use Brain\Monkey\Functions;

/** A REST save answers with the words the old redirect showed (deviation 143). */
final class NoticeTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		Functions\stubTranslationFunctions();
	}

	public function test_a_code_gives_the_toast_words(): void {
		self::assertSame( 'Settings saved.', Notice::text( 'saved' ) );
		self::assertSame( 'Tracking ID Saved!', Notice::text( 'tracking-saved' ) );
		self::assertSame( '', Notice::text( 'nothing-like-this' ) );
	}

	public function test_refusals_and_unvalidated_credentials_are_failures(): void {
		self::assertTrue( Notice::failure( 'icon-refused' ) );
		self::assertTrue( Notice::failure( 'tracking-refused' ) );
		self::assertTrue( Notice::failure( 'amazon-unverified-kept' ) );
		self::assertFalse( Notice::failure( 'saved' ) );
		self::assertFalse( Notice::failure( 'partly-saved' ) );
	}
}
