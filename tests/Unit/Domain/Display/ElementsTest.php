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

	public function test_price_follows_show_price_until_it_is_saved(): void {
		$inherited = Elements::from_array( array( 'badge' => true ) ); // no price stored

		self::assertFalse( $inherited->with_show_price( false )->visible( 'price' ) );
		self::assertTrue( $inherited->with_show_price( true )->visible( 'price' ) );
		self::assertArrayNotHasKey( 'price', $inherited->with_show_price( false )->to_array() ); // keeps inheriting

		$saved = $inherited->with( 'price', true );
		self::assertTrue( $saved->with_show_price( false )->visible( 'price' ) ); // the saved value wins
		self::assertTrue( $saved->to_array()['price'] );
	}
}
