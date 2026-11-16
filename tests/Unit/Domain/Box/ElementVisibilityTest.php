<?php
namespace Affibloom\Tests\Unit\Domain\Box;

use Affibloom\Domain\Box\BoxOverrides;
use Affibloom\Domain\Box\ElementVisibility;
use Affibloom\Domain\Display\Elements;
use PHPUnit\Framework\TestCase;

/**
 * A box's element visibility: the Single element switches, then the box's own hides (F5 R60-R62; Task 18J).
 */
final class ElementVisibilityTest extends TestCase {

	/**
	 * The switches decide; the hidden ones are listed in Lasso's order for the wrapper's flags.
	 */
	public function test_the_element_switches_decide(): void {
		$visibility = ElementVisibility::of(
			Elements::from_array( array( 'price' => false, 'title' => false, 'timestamp' => false ) ),
			BoxOverrides::from_block( array() )
		);

		self::assertFalse( $visibility->shows( 'title' ) );
		self::assertTrue( $visibility->shows( 'image' ) );
		self::assertSame( array( 'title', 'price', 'timestamp' ), $visibility->hidden() );
	}

	/**
	 * A box's `hide` adds to the switches (Lasso's compose_display_element_visibility).
	 */
	public function test_a_boxs_hides_add_to_the_switches(): void {
		$visibility = ElementVisibility::of(
			Elements::defaults(),
			BoxOverrides::from_block(
				array(
					'hideBadge'       => true,
					'hideImage'       => true,
					'hideDescription' => true,
					'hideTitle'       => true,
				)
			)
		);

		self::assertSame( array( 'badge', 'image', 'title', 'desc' ), array_values( array_intersect( $visibility->hidden(), array( 'badge', 'image', 'title', 'desc' ) ) ) );
	}

	/**
	 * R62: with the Badge element off, a badge with text takes the override class and stays.
	 */
	public function test_a_hidden_badge_element_lets_a_badge_with_text_show(): void {
		self::assertTrue( ElementVisibility::of( Elements::from_array( array( 'badge' => false ) ), BoxOverrides::from_block( array() ) )->badge_overrides() );
		self::assertFalse( ElementVisibility::of( Elements::defaults(), BoxOverrides::from_block( array() ) )->badge_overrides() );
	}
}
