<?php
namespace Affibloom\Tests\Unit\Domain\Display;

use PHPUnit\Framework\TestCase;
use Affibloom\Domain\Display\Theme;

final class ThemeTest extends TestCase {
	public function test_lassos_eight_themes_are_offered(): void {
		self::assertSame( array( 'Cactus', 'Cutter', 'Flow', 'Geek', 'Lab', 'Llama', 'Money', 'Splash' ), Theme::NAMES );
	}

	public function test_a_name_is_read_whatever_its_case_and_anything_else_is_the_default(): void {
		self::assertSame( 'Llama', Theme::from( 'llama' )->name() );
		self::assertSame( 'Cactus', Theme::from( 'Nope' )->name() );
		self::assertSame( 'Cactus', Theme::fallback()->name() );
	}
}
