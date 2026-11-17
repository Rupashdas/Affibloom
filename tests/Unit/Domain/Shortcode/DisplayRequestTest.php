<?php
namespace Affibloom\Tests\Unit\Domain\Shortcode;

use Affibloom\Domain\Shortcode\DisplayKind;
use Affibloom\Domain\Shortcode\DisplayRequest;
use Affibloom\Tests\Unit\TestCase;

/**
 * A Product box block asks for what the shortcode would (F4 D3, Task 23C): its type and its product.
 */
final class DisplayRequestTest extends TestCase {

	public function test_a_block_with_a_product_and_no_type_is_the_single_box(): void {
		$request = DisplayRequest::from_block( array( 'productId' => 12 ) );

		self::assertNotNull( $request );
		self::assertSame( DisplayKind::Single, $request->kind );
		self::assertSame( 12, $request->address->id );
	}

	public function test_the_button_and_image_types_ask_for_their_display(): void {
		self::assertSame( DisplayKind::Button, DisplayRequest::from_block( array( 'type' => 'button', 'productId' => 12 ) )?->kind );
		self::assertSame( DisplayKind::Image, DisplayRequest::from_block( array( 'type' => 'image', 'productId' => 12 ) )?->kind );
		self::assertSame( DisplayKind::Single, DisplayRequest::from_block( array( 'type' => 'single', 'productId' => 12 ) )?->kind );
	}

	public function test_the_group_types_ask_for_their_group_by_its_slug(): void {
		$grid = DisplayRequest::from_block( array( 'type' => 'grid', 'groupId' => 4 ), 'best-shoes' );
		self::assertSame( DisplayKind::Grid, $grid?->kind );
		self::assertSame( 'best-shoes', $grid?->attributes['group'] );
		self::assertSame( DisplayKind::List, DisplayRequest::from_block( array( 'type' => 'list', 'groupId' => 4 ), 'best-shoes' )?->kind );
		self::assertSame( DisplayKind::Gallery, DisplayRequest::from_block( array( 'type' => 'gallery', 'groupId' => 4 ), 'best-shoes' )?->kind );
		// A group that is gone draws nothing, though the block still names a display (the preview answers, empty).
		self::assertNull( DisplayRequest::from_block( array( 'type' => 'list', 'groupId' => 4 ) ) );
		self::assertTrue( DisplayRequest::names_display( array( 'type' => 'list', 'groupId' => 4 ) ) );
		self::assertNull( DisplayRequest::from_block( array( 'type' => 'grid' ), 'best-shoes' ) );
		self::assertFalse( DisplayRequest::names_display( array( 'type' => 'grid', 'productId' => 4 ) ) );
	}

	public function test_the_blocks_overrides_reach_the_request_as_the_shortcodes(): void {
		$request = DisplayRequest::from_block(
			array(
				'type'      => 'single',
				'productId' => 12,
				'overrides' => array(
					'badge'   => 'Best Overall',
					'price'   => 'hide',
					'columns' => '3',
				),
			)
		);
		self::assertSame( 'Best Overall', $request?->attributes['badge'] );
		self::assertSame( 'hide', $request?->attributes['price'] );
		// Not a Single field: dropped.
		self::assertArrayNotHasKey( 'columns', $request?->attributes ?? array() );
	}

	public function test_no_product_or_an_unknown_type_asks_for_nothing(): void {
		self::assertNull( DisplayRequest::from_block( array( 'type' => 'single' ) ) );
		self::assertNull( DisplayRequest::from_block( array( 'type' => 'single', 'productId' => 0 ) ) );
		self::assertNull( DisplayRequest::from_block( array( 'type' => 'carousel', 'productId' => 12 ) ) );
		self::assertNull( DisplayRequest::from_block( array( 'type' => array( 'single' ), 'productId' => 12 ) ) );
	}
}
