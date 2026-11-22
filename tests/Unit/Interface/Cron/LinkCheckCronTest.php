<?php
namespace Affibloom\Tests\Unit\Interface\Cron;

use Affibloom\Interface\Cron\LinkCheckCron;
use Affibloom\Tests\Unit\TestCase;
use Brain\Monkey\Functions;
use ReflectionClass;

/**
 * The link check's catch-up (exit check finding, 2026-11-22) runs on admin_init, which WordPress also fires for a
 * visitor's admin-ajax.php and admin-post.php requests: as the link index's (37A, S4), a request without Affibloom's
 * capability reads nothing and queues nothing.
 */
final class LinkCheckCronTest extends TestCase {

	public function test_a_request_without_the_capability_queues_nothing(): void {
		Functions\expect( 'current_user_can' )->once()->with( 'affibloom_products' )->andReturn( false );
		Functions\expect( 'wp_next_scheduled' )->never();
		// Built without its parts: any use of one (the check, the job) would fail the test.
		$cron = ( new ReflectionClass( LinkCheckCron::class ) )->newInstanceWithoutConstructor();

		$cron->catch_up();

		self::assertTrue( true, 'returned before touching the pass' );
	}
}
