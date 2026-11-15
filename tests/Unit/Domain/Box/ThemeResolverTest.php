<?php
namespace Affibloom\Tests\Unit\Domain\Box;

use Affibloom\Domain\Box\LayoutFamily;
use Affibloom\Domain\Box\ThemeResolver;
use Affibloom\Domain\Display\Theme;
use PHPUnit\Framework\TestCase;

/**
 * Lasso's theme order for a Single box (F5 R3, AC8; Task 18E).
 */
final class ThemeResolverTest extends TestCase {

	public function test_the_display_types_theme_draws_when_nothing_else_names_one(): void {
		self::assertSame( 'Cutter', ThemeResolver::resolve( Theme::from( 'Cutter' ), null, '' )->name() );
	}

	public function test_the_products_theme_wins_over_the_display_types(): void {
		self::assertSame( 'Flow', ThemeResolver::resolve( Theme::from( 'Lab' ), Theme::from( 'Flow' ), '' )->name() );
	}

	public function test_the_boxs_theme_wins_over_the_products(): void {
		self::assertSame( 'Geek', ThemeResolver::resolve( Theme::from( 'Lab' ), Theme::from( 'Flow' ), 'Geek' )->name() );
	}

	/**
	 * Lasso compares the names without case and ignores an empty or blank box value.
	 */
	public function test_names_compare_without_case_and_a_blank_box_value_is_ignored(): void {
		self::assertSame( 'Splash', ThemeResolver::resolve( Theme::from( 'Lab' ), null, 'sPLASH' )->name() );
		self::assertSame( 'Lab', ThemeResolver::resolve( Theme::from( 'Lab' ), null, '  ' )->name() );
	}

	/**
	 * An unknown name, or Lasso's "Array" from a broken setting, draws Cactus.
	 */
	public function test_an_unknown_box_theme_draws_cactus(): void {
		self::assertSame( 'Cactus', ThemeResolver::resolve( Theme::from( 'Lab' ), null, 'Nonsense' )->name() );
		self::assertSame( 'Cactus', ThemeResolver::resolve( Theme::from( 'Lab' ), Theme::from( 'Flow' ), 'Array' )->name() );
	}

	/**
	 * Cutter, Flow, Geek and Splash draw the 2-box layout (R5).
	 */
	public function test_the_four_2_box_themes_draw_two_boxes(): void {
		foreach ( array( 'Cutter', 'Flow', 'Geek', 'Splash' ) as $name ) {
			self::assertSame( LayoutFamily::TwoBox, LayoutFamily::of( ThemeResolver::resolve( Theme::from( 'Lab' ), null, $name ) ), $name );
		}
	}
}
