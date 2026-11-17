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

	public function test_no_product_or_an_unknown_type_asks_for_nothing(): void {
		self::assertNull( DisplayRequest::from_block( array( 'type' => 'single' ) ) );
		self::assertNull( DisplayRequest::from_block( array( 'type' => 'single', 'productId' => 0 ) ) );
		self::assertNull( DisplayRequest::from_block( array( 'type' => 'carousel', 'productId' => 12 ) ) );
		self::assertNull( DisplayRequest::from_block( array( 'type' => array( 'single' ), 'productId' => 12 ) ) );
	}
}
