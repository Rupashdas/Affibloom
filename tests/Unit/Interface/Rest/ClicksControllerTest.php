<?php
namespace Affibloom\Tests\Unit\Interface\Rest;

use Affibloom\Interface\Rest\ClicksController;
use Affibloom\Tests\Unit\TestCase;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

/**
 * The reader's address the click counter keys repeats by (Lasso's `lasso_get_user_ip_address`; Task GF5).
 */
final class ClicksControllerTest extends TestCase {

	protected function tearDown(): void {
		unset( $_SERVER['REMOTE_ADDR'] );
		parent::tearDown();
	}

	public function test_site_code_can_give_the_readers_address(): void {
		$_SERVER['REMOTE_ADDR'] = '198.51.100.7';
		Functions\when( 'wp_unslash' )->returnArg();
		Functions\when( 'sanitize_text_field' )->returnArg();
		Filters\expectApplied( 'affibloom_user_ip_address' )->once()->with( '198.51.100.7' )->andReturn( '203.0.113.9' );

		self::assertSame( '203.0.113.9', ClicksController::reader_address() );
	}

	public function test_without_a_filter_it_is_remote_addr(): void {
		$_SERVER['REMOTE_ADDR'] = '198.51.100.7';
		Functions\when( 'wp_unslash' )->returnArg();
		Functions\when( 'sanitize_text_field' )->returnArg();

		self::assertSame( '198.51.100.7', ClicksController::reader_address() );
	}
}
