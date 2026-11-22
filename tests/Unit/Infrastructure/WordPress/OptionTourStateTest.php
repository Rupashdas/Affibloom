<?php
namespace Affibloom\Tests\Unit\Infrastructure\WordPress;

use Affibloom\Infrastructure\WordPress\OptionTourState;
use Affibloom\Tests\Unit\TestCase;
use Brain\Monkey\Functions;

/**
 * F38 R59, R62, R63 (Task ON8): the tour shows until the site-wide option says it was ended; ending it writes '1'.
 */
final class OptionTourStateTest extends TestCase {

	public function test_the_tour_is_due_until_ended_for_the_site(): void {
		Functions\when( 'get_option' )->justReturn( '' );
		self::assertFalse( ( new OptionTourState() )->done() );

		Functions\expect( 'update_option' )->once()->with( 'affibloom_tour_done', '1', true );
		( new OptionTourState() )->finish();
	}

	public function test_an_ended_tour_stays_ended(): void {
		Functions\when( 'get_option' )->justReturn( '1' );
		self::assertTrue( ( new OptionTourState() )->done() );
	}
}
