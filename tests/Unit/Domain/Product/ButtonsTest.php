<?php
namespace Affibloom\Tests\Unit\Domain\Product;

use Affibloom\Domain\Product\Button;
use Affibloom\Domain\Product\Buttons;
use PHPUnit\Framework\TestCase;

final class ButtonsTest extends TestCase {
	public function test_the_added_buttons_keep_their_order(): void {
		$buttons = Buttons::from_array(
			array(
				array( 'url' => 'https://www.example.com/men', 'text' => 'Men', 'new_tab' => true, 'nofollow' => true ),
				array( 'url' => 'https://www.example.com/women', 'text' => 'Women', 'new_tab' => false, 'nofollow' => true ),
			)
		);

		self::assertSame(
			array( 'https://www.example.com/men', 'https://www.example.com/women' ),
			array_map( static fn( Button $b ): string => $b->url, $buttons->all() )
		);
		self::assertFalse( $buttons->all()[1]->new_tab );
		self::assertSame( 0, $buttons->main_position() );
	}

	/**
	 * The default button (the product's main link) sits anywhere from first to last (the user, 2026-11-13): a stored
	 * position past the end means last, a negative one first.
	 */
	public function test_the_default_buttons_position_is_kept_within_the_list(): void {
		$two = array( array( 'url' => 'a' ), array( 'url' => 'b' ) );

		self::assertSame( 1, Buttons::from_array( $two, 1 )->main_position() );
		self::assertSame( 2, Buttons::from_array( $two, 9 )->main_position() );
		self::assertSame( 0, Buttons::from_array( $two, -3 )->main_position() );
		self::assertSame( 0, Buttons::from_array( array(), 4 )->main_position() );
	}

	/**
	 * Lasso keeps a second button's text without its URL on 123 records here: the text is kept (the page shows it),
	 * only a button with neither is dropped.
	 */
	public function test_a_button_with_neither_link_nor_text_is_dropped_and_a_text_alone_is_kept(): void {
		$buttons = Buttons::from_array(
			array(
				array( 'url' => '', 'text' => 'Check Price' ),
				array( 'url' => '  ', 'text' => '' ),
				'junk',
				array( 'url' => 'https://www.example.com/x' ),
			)
		);

		self::assertCount( 2, $buttons->all() );
		self::assertSame( 'Check Price', $buttons->all()[0]->text );
		self::assertSame( '', $buttons->all()[0]->url );
	}

	/** Stored by WP-CLI's JSON or by PHP: booleans, "1"/"0", or missing (Lasso's new-record default, on). */
	public function test_toggles_read_every_stored_form(): void {
		$buttons = Buttons::from_array(
			array(
				array( 'url' => 'a', 'new_tab' => '0', 'nofollow' => '1' ),
				array( 'url' => 'b' ),
			)
		);

		self::assertFalse( $buttons->all()[0]->new_tab );
		self::assertTrue( $buttons->all()[0]->nofollow );
		self::assertTrue( $buttons->all()[1]->new_tab );
		self::assertTrue( $buttons->all()[1]->nofollow );
	}

	public function test_it_survives_storage(): void {
		$buttons = Buttons::from_array( array( array( 'url' => 'https://www.example.com/a', 'text' => 'A', 'new_tab' => false, 'nofollow' => true ) ) );

		self::assertEquals( $buttons, Buttons::from_array( $buttons->to_array() ) );
		self::assertSame(
			array( array( 'url' => 'https://www.example.com/a', 'text' => 'A', 'new_tab' => false, 'nofollow' => true ) ),
			$buttons->to_array()
		);
	}
}
