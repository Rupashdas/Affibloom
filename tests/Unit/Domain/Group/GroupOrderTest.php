<?php
namespace Affibloom\Tests\Unit\Domain\Group;

use Affibloom\Domain\Group\GroupOrder;
use PHPUnit\Framework\TestCase;

final class GroupOrderTest extends TestCase {
	public function test_members_follow_the_stored_order_and_strangers_are_skipped(): void {
		self::assertSame( array( 3, 1, 2 ), GroupOrder::rank( array( 1, 2, 3 ), array( 3, 9, 1, 3 ) ) );
	}

	public function test_members_missing_from_the_stored_order_come_last_by_id(): void {
		self::assertSame( array( 5, 2, 4 ), GroupOrder::rank( array( 2, 4, 5 ), array( 5 ) ) );
	}

	public function test_append_puts_a_product_last_once(): void {
		self::assertSame( array( 1, 3, 2 ), GroupOrder::append( array( 1, 2, 3 ), 2 ) );
	}

	public function test_move_swaps_with_a_neighbour_and_stops_at_the_ends(): void {
		self::assertSame( array( 2, 1, 3 ), GroupOrder::move( array( 1, 2, 3 ), 2, -1 ) );
		self::assertSame( array( 1, 3, 2 ), GroupOrder::move( array( 1, 2, 3 ), 2, 1 ) );
		self::assertSame( array( 1, 2, 3 ), GroupOrder::move( array( 1, 2, 3 ), 1, -1 ) );
		self::assertSame( array( 1, 2, 3 ), GroupOrder::move( array( 1, 2, 3 ), 3, 1 ) );
		self::assertSame( array( 1, 2, 3 ), GroupOrder::move( array( 1, 2, 3 ), 9, 1 ) );
	}
}
