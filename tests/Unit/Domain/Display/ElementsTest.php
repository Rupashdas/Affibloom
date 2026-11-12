<?php
namespace Affibloom\Tests\Unit\Domain\Display;

use PHPUnit\Framework\TestCase;
use Affibloom\Domain\Display\Elements;

final class ElementsTest extends TestCase {
	public function test_every_element_is_shown_by_default_as_in_lasso(): void {
		$elements = Elements::defaults();

		self::assertSame( array( 'badge', 'image', 'title', 'price', 'prime', 'desc', 'proscons', 'primary', 'secondary', 'timestamp' ), Elements::KEYS );
		self::assertSame( 10, $elements->shown() );
	}

	public function test_an_element_can_be_hidden(): void {
		$elements = Elements::defaults()->with( 'price', false )->with( 'timestamp', false );

		self::assertFalse( $elements->visible( 'price' ) );
		self::assertTrue( $elements->visible( 'badge' ) );
		self::assertSame( 8, $elements->shown() );
	}

	public function test_stored_flags_are_read_and_missing_ones_are_shown(): void {
		$elements = Elements::from_array(
			array(
				'price'   => false,
				'unknown' => false,
				'prime'   => 'yes', // not a bool: ignored
			)
		);

		self::assertFalse( $elements->visible( 'price' ) );
		self::assertTrue( $elements->visible( 'prime' ) );
		self::assertArrayNotHasKey( 'unknown', $elements->to_array() );
	}

	public function test_an_unknown_element_is_a_programming_error(): void {
		$this->expectException( \InvalidArgumentException::class );
		Elements::defaults()->with( 'disclosure', false ); // stored in the site-wide settings instead (B5)
	}
}
