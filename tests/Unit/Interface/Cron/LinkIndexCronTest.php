<?php
namespace Affibloom\Tests\Unit\Interface\Cron;

use Affibloom\Interface\Cron\LinkIndexCron;
use Affibloom\Tests\Unit\TestCase;
use Brain\Monkey\Functions;
use ReflectionClass;

/**
 * Task 37A, finding S4: the catch-up runs on admin_init, which WordPress also fires for a visitor's admin-ajax.php and
 * admin-post.php requests. It is the administrator's page load moving their rebuild on (Task 35F), so a request
 * without Affibloom's capability runs no batch and reads nothing.
 */
final class LinkIndexCronTest extends TestCase {

	public function test_a_request_without_the_capability_runs_no_batch(): void {
		Functions\expect( 'current_user_can' )->once()->with( 'affibloom_products' )->andReturn( false );
		Functions\expect( 'wp_next_scheduled' )->never();
		// Built without its parts: any use of one (the state, the job, the settings) would fail the test.
		$cron = ( new ReflectionClass( LinkIndexCron::class ) )->newInstanceWithoutConstructor();

		$cron->catch_up();

		self::assertTrue( true, 'returned before touching the queue' );
	}
}
