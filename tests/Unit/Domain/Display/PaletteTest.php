<?php
namespace Affibloom\Tests\Unit\Domain\Display;

use PHPUnit\Framework\TestCase;
use Affibloom\Domain\Display\Palette;

final class PaletteTest extends TestCase {
	public function test_the_defaults_are_lassos_colours_as_hex(): void {
		$palette = Palette::defaults();
		self::assertSame( '#000000', $palette->value( 'main' ) );   // Lasso writes "black"
		self::assertSame( '#e06470', $palette->value( 'cons' ) );
		self::assertSame( 'Our Review', $palette->value( 'secondary_button_text' ) );
	}

	public function test_stored_values_fill_in_and_junk_is_ignored(): void {
		$palette = Palette::from_array(
			array(
				'main'    => '#000000',
				'unknown' => 'x',
				'title'   => array( 'not a string' ),
			)
		);

		self::assertSame( '#000000', $palette->value( 'main' ) );
		self::assertSame( '#000000', $palette->value( 'title' ) ); // the default, not the array
		self::assertArrayNotHasKey( 'unknown', $palette->to_array() );
	}

	public function test_a_value_can_be_replaced_and_an_unknown_key_is_a_programming_error(): void {
		self::assertSame( 'Buy It', Palette::defaults()->with( 'primary_button_text', 'Buy It' )->value( 'primary_button_text' ) );

		$this->expectException( \InvalidArgumentException::class );
		Palette::defaults()->with( 'nope', 'x' );
	}
}
