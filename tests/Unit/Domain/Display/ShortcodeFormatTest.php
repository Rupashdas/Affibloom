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
				'table'     => 0,
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

	/** Task 23F (F4 R24): a comparison table as text and back. */
	public function test_a_table_is_written_and_read_by_its_id(): void {
		self::assertSame( '[affibloom type="table" id="7"]', ShortcodeFormat::format( 'table', 0, '', array( 'badge' => 'x' ), 7 ) );
		self::assertSame( '', ShortcodeFormat::format( 'table', 0, '', array() ) );
		$table = ShortcodeFormat::read( array( 'type' => 'table', 'id' => '7' ) );
		self::assertSame( array( 'table', 7, 0 ), array( $table['type'] ?? null, $table['table'] ?? null, $table['product'] ?? null ) );
		self::assertNull( ShortcodeFormat::read( array( 'type' => 'table' ) ) );
	}

	/**
	 * Task LM2 (F10 D16): what a block would lose of a shortcode's attributes, and its schema panel's values.
	 */
	public function test_a_block_names_what_it_cannot_hold(): void {
		$single = array(
			'id'               => '7',
			'badge'            => 'Best',
			'price'            => 'show',
			'rating'           => '4.5',
			'pros'             => 'Light',
			'schema_review'    => 'enable',
			'schema_price'     => '59',
			'schema_pros_cons' => 'disable',
		);
		self::assertSame( array( 'rating', 'pros' ), ShortcodeFormat::lost( 'single', $single ) );
		self::assertSame( array( 'schemaReview' => true, 'schemaPrice' => '59' ), ShortcodeFormat::schema( $single ) );
		self::assertSame( array(), ShortcodeFormat::lost( 'grid', array( 'type' => 'grid', 'category' => 'shoes', 'limit' => '3' ) ) );
		self::assertSame( array( 'anchor_id' ), ShortcodeFormat::lost( 'table', array( 'type' => 'table', 'id' => '7', 'anchor_id' => 'x' ) ) );
	}
}
