<?php
namespace Affibloom\Tests\Unit\Domain\Display;

use Affibloom\Domain\Display\ShortcodeFormat;
use Affibloom\Tests\Unit\TestCase;

/**
 * The block's shortcode box (F4 R7, R41, Task 24C): a display as text in Lasso's order, and back.
 */
final class ShortcodeFormatTest extends TestCase {

	public function test_a_display_is_written_target_first_then_its_overrides_in_lassos_order(): void {
		self::assertSame(
			'[affibloom id="12" title="Shoes" badge="Best Overall" price="hide"]',
			ShortcodeFormat::format(
				'single',
				12,
				'',
				array(
					'price' => 'hide',
					'badge' => 'Best Overall',
					'title' => 'Shoes',
				)
			)
		);
		self::assertSame( '[affibloom id="12" type="button"]', ShortcodeFormat::format( 'button', 12, '', array() ) );
		self::assertSame( '[affibloom type="gallery" group="pair" limit="4"]', ShortcodeFormat::format( 'gallery', 0, 'pair', array( 'limit' => '4' ) ) );
		self::assertSame( '', ShortcodeFormat::format( 'grid', 0, '', array() ) );
		self::assertSame( '', ShortcodeFormat::format( 'single', 0, '', array() ) );
	}

	public function test_typed_attributes_read_back_as_the_same_display(): void {
		self::assertSame(
			array(
				'type'      => 'button',
				'product'   => 12,
				'group'     => '',
				'overrides' => array( 'primary_text' => 'Buy' ),
			),
			ShortcodeFormat::read(
				array(
					'id'           => '12',
					'type'         => 'button',
					'primary_text' => 'Buy',
					'title'        => 'Not a button field',
				)
			)
		);
		$list = ShortcodeFormat::read(
			array(
				'type'     => 'list',
				'category' => 'pair',
			)
		);
		self::assertSame( 'list', $list['type'] ?? null );
		self::assertSame( 'pair', $list['group'] ?? null );
	}

	public function test_text_that_names_no_target_reads_as_nothing(): void {
		self::assertNull( ShortcodeFormat::read( array( 'badge' => 'Hi' ) ) );
		self::assertNull( ShortcodeFormat::read( array( 'type' => 'grid' ) ) );
		self::assertNull( ShortcodeFormat::read( array( 'id' => 'abc' ) ) );
	}
}
