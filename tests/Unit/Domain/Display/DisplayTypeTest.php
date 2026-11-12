<?php
namespace Affibloom\Tests\Unit\Domain\Display;

use PHPUnit\Framework\TestCase;
use Affibloom\Domain\Display\DisplayType;

final class DisplayTypeTest extends TestCase {
	public function test_the_three_types_are_offered_in_order(): void {
		self::assertSame( array( 'single', 'grid', 'list' ), array_map( static fn( $t ) => $t->key(), DisplayType::all() ) );
	}

	public function test_an_unknown_key_is_read_as_the_single_box(): void {
		self::assertSame( 'single', DisplayType::from( 'table' )->key() );
		self::assertSame( 'grid', DisplayType::from( ' GRID ' )->key() );
	}
}
