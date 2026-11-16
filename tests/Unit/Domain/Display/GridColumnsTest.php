<?php
namespace Affibloom\Tests\Unit\Domain\Display;

use Affibloom\Domain\Display\GridColumns;
use PHPUnit\Framework\TestCase;

/**
 * A grid's columns (F30 R14, N1; Task DT3).
 */
final class GridColumnsTest extends TestCase {

	/**
	 * The shortcode's count in both modes (N1); without one, the site's when customised, else 2.
	 */
	public function test_the_columns(): void {
		self::assertSame( 4, GridColumns::of( 4, false, 3 ) );
		self::assertSame( 4, GridColumns::of( 4, true, 3 ) );
		self::assertSame( 3, GridColumns::of( null, true, 3 ) );
		self::assertSame( 2, GridColumns::of( null, false, 3 ) );
	}
}
