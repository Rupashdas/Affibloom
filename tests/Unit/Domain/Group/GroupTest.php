<?php
namespace Affibloom\Tests\Unit\Domain\Group;

use Affibloom\Domain\Group\Group;
use Affibloom\Domain\Group\GroupPage;
use PHPUnit\Framework\TestCase;

final class GroupTest extends TestCase {
	public function test_a_description_of_237_characters_or_fewer_is_shown_whole(): void {
		$text = str_repeat( 'x', 237 );

		self::assertSame( $text, ( new Group( 1, 'A', 'a', $text, 0 ) )->list_description() );
		self::assertSame( '', ( new Group( 1, 'A', 'a', '', 0 ) )->list_description() );
	}

	public function test_a_longer_description_is_cut_at_237_characters_and_three_dots(): void {
		$shown = ( new Group( 1, 'A', 'a', str_repeat( 'x', 238 ), 0 ) )->list_description();

		self::assertSame( str_repeat( 'x', 237 ) . '...', $shown );
	}

	public function test_the_cut_counts_characters_not_bytes(): void {
		$shown = ( new Group( 1, 'A', 'a', str_repeat( 'é', 300 ), 0 ) )->list_description();

		self::assertSame( str_repeat( 'é', 237 ) . '...', $shown );
	}

	public function test_a_page_knows_its_range_and_never_runs_past_the_last_page(): void {
		$page = new GroupPage( array(), 11, 2, 10 );

		self::assertSame( 2, $page->pages() );
		self::assertSame( 11, $page->first() );
		self::assertSame( 11, $page->last() );
		self::assertSame( 2, ( new GroupPage( array(), 11, 9, 10 ) )->current() );
		self::assertSame( 1, ( new GroupPage( array(), 0, 1, 10 ) )->pages() );
		self::assertSame( 0, ( new GroupPage( array(), 0, 1, 10 ) )->first() );
	}
}
