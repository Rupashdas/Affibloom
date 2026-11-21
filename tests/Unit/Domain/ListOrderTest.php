<?php
namespace Affibloom\Tests\Unit\Domain;

use Affibloom\Domain\Product\ListOrder;
use PHPUnit\Framework\TestCase;

/**
 * The Dashboard's header sorts (F19 R1, R2, D8, D10; Task 13K).
 */
final class ListOrderTest extends TestCase {

	public function test_alert_goes_ascending_descending_then_off(): void {
		$order = new ListOrder();
		$seen  = array();
		for ( $i = 0; $i < 4; $i++ ) {
			$order  = $order->next( ListOrder::ALERT );
			$seen[] = $order->column . ':' . $order->direction;
		}

		self::assertSame( array( 'alert:asc', 'alert:desc', ':', 'alert:asc' ), $seen );
	}

	public function test_locations_and_clicks_start_descending_and_alternate(): void {
		$order = ( new ListOrder() )->next( ListOrder::CLICKS );
		self::assertSame( 'desc', $order->direction );
		$order = $order->next( ListOrder::CLICKS )->next( ListOrder::CLICKS );
		self::assertSame( 'desc', $order->direction );
		self::assertSame( 'asc', $order->next( ListOrder::CLICKS )->direction );

		// Another column starts afresh.
		$other = $order->next( ListOrder::LOCATIONS );
		self::assertSame( array( 'locations', 'desc', '' ), array( $other->column, $other->direction, $other->direction_of( ListOrder::CLICKS ) ) );
	}

	public function test_anything_outside_the_lists_is_the_default(): void {
		self::assertTrue( ListOrder::from_input( 'post_title', 'asc' )->is_default() );
		self::assertTrue( ListOrder::from_input( 'clicks', 'up' )->is_default() );
		self::assertTrue( ListOrder::from_input( '', '' )->is_default() );
		self::assertFalse( ListOrder::from_input( 'locations', 'asc' )->is_default() );
	}
}
