<?php
namespace Affibloom\Tests\Unit\Domain\Display;

use PHPUnit\Framework\TestCase;
use Affibloom\Domain\Display\DisplayType;
use Affibloom\Domain\Display\Layout;

/**
 * Lasso's rules: Helper::get_display_layout_defaults, normalize_display_layout_bucket,
 * canonicalize_display_layout, coerce_display_layout_image_position and the mobile base and overrides.
 */
final class LayoutTest extends TestCase {
	public function test_the_defaults_differ_for_grid(): void {
		$single = Layout::defaults( DisplayType::single() );
		$grid   = Layout::defaults( DisplayType::grid() );

		self::assertSame( array( 'side-by-side', 'right', 'left', 'spread', 'hug', 'stack', 'contain' ), array_values( $single->desktop() ) );
		self::assertSame( array( 'stacked', 'top', 'center', 'spread', 'hug', 'stack', 'contain' ), array_values( $grid->desktop() ) );
	}

	public function test_image_position_follows_the_orientation(): void {
		$layout = Layout::defaults( DisplayType::single() )->with( 'imagePosition', 'left' );
		self::assertSame( 'left', $layout->value( 'imagePosition' ) );

		$stacked = $layout->with( 'orientation', 'stacked' );
		self::assertSame( 'top', $stacked->value( 'imagePosition' ) );                                     // left -> top
		self::assertSame( 'bottom', $stacked->with( 'imagePosition', 'right' )->value( 'imagePosition' ) ); // right -> bottom
		self::assertSame( 'right', $stacked->with( 'orientation', 'side-by-side' )->with( 'imagePosition', 'bottom' )->value( 'imagePosition' ) );
	}

	public function test_an_unknown_value_falls_back_to_the_type_default(): void {
		self::assertSame( 'left', Layout::defaults( DisplayType::single() )->with( 'align', 'diagonal' )->value( 'align' ) );
		self::assertSame( 'center', Layout::defaults( DisplayType::grid() )->with( 'align', 'diagonal' )->value( 'align' ) );
	}

	public function test_grid_is_always_stacked(): void {
		$grid = Layout::defaults( DisplayType::grid() )->with( 'orientation', 'side-by-side' );
		self::assertSame( 'stacked', $grid->value( 'orientation' ) );

		$stored = Layout::from_array( DisplayType::grid(), array( 'orientation' => 'side-by-side', 'imagePosition' => 'left' ) );
		self::assertSame( 'stacked', $stored->value( 'orientation' ) );
		self::assertSame( 'top', $stored->value( 'imagePosition' ) );
	}

	public function test_the_phone_layout_only_holds_the_six_keys_and_can_be_cleared(): void {
		$layout = Layout::defaults( DisplayType::single() )->with_phone( 'align', 'center' )->with_phone( 'mobileOverflow', 'swipe' );

		self::assertSame( array( 'align' => 'center' ), $layout->phone() );    // mobileOverflow is not a phone key
		self::assertSame( 'left', $layout->value( 'align' ) );                 // desktop untouched
		self::assertSame( array(), $layout->clear_phone()->phone() );
	}

	public function test_on_phones_untouched_keys_follow_desktop_except_button_alignment(): void {
		$phone = Layout::defaults( DisplayType::single() )->with( 'buttonAlign', 'left' )->with( 'ctaWidth', 'full' )->phone_effective();

		self::assertSame( 'center', $phone['buttonAlign'] ); // Lasso's phone base centres the buttons
		self::assertSame( 'full', $phone['ctaWidth'] );      // the rest follows desktop
		self::assertSame( 'side-by-side', $phone['orientation'] );
	}

	public function test_a_phone_image_position_is_kept_in_the_phone_orientation(): void {
		$layout = Layout::defaults( DisplayType::single() )->with_phone( 'orientation', 'stacked' )->with_phone( 'imagePosition', 'left' );

		self::assertSame( 'top', $layout->phone()['imagePosition'] );
		self::assertSame( 'side-by-side', $layout->value( 'orientation' ) ); // desktop untouched
	}

	public function test_grid_has_no_phone_orientation(): void {
		self::assertSame( array(), Layout::defaults( DisplayType::grid() )->with_phone( 'orientation', 'side-by-side' )->phone() );
	}

	public function test_it_survives_a_round_trip_through_storage(): void {
		$before = Layout::defaults( DisplayType::list() )->with( 'ctaWidth', 'full' )->with_phone( 'align', 'center' );
		$after  = Layout::from_array( DisplayType::list(), $before->to_array() );

		self::assertSame( 'full', $after->value( 'ctaWidth' ) );
		self::assertSame( array( 'align' => 'center' ), $after->phone() );
		self::assertArrayNotHasKey( 'mobile', Layout::defaults( DisplayType::list() )->to_array() ); // no empty override
	}
}
