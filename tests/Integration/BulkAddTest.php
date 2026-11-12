<?php
namespace Affibloom\Tests\Integration;

use WP_REST_Request;
use WP_REST_Response;
use WP_UnitTestCase;

/**
 * /affibloom/v1/products/bulk and the affibloom_bulk_add event (Task 11C). Runs only where wp-env is available
 * (progress.md, deviation 10).
 */
final class BulkAddTest extends WP_UnitTestCase {
	protected function setUp(): void {
		parent::setUp();
		delete_option( 'affibloom_bulk_add' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	private function call( string $method, ?array $urls = null ): WP_REST_Response {
		$request = new WP_REST_Request( $method, '/affibloom/v1/products/bulk' );
		if ( null !== $urls ) {
			$request->set_body_params( array( 'urls' => $urls ) );
		}
		return rest_get_server()->dispatch( $request );
	}

	public function test_a_run_takes_ten_links_and_the_event_adds_them(): void {
		$links = array();
		for ( $i = 1; $i <= 11; $i++ ) {
			$links[] = "https://www.example.com/bulk-$i";
		}

		$started = $this->call( 'POST', $links );
		self::assertSame( 202, $started->get_status() );
		self::assertSame( 10, $started->get_data()['data']['total'] );
		self::assertNotFalse( wp_next_scheduled( 'affibloom_bulk_add' ) );

		do_action( 'affibloom_bulk_add' );

		$progress = $this->call( 'GET' )->get_data()['data'];
		self::assertFalse( $progress['running'] );
		self::assertSame( 100, $progress['percent'] );
		self::assertCount( 10, get_posts( array( 'post_type' => 'affibloom_product', 'posts_per_page' => -1 ) ) );
	}

	public function test_a_second_run_is_refused_while_the_first_is_going(): void {
		$this->call( 'POST', array( 'https://www.example.com/a' ) );

		$second = $this->call( 'POST', array( 'https://www.example.com/b' ) );

		self::assertSame( 409, $second->get_status() );
		self::assertSame( 'affibloom_bulk_running', $second->get_data()['code'] );
	}

	public function test_only_blank_lines_are_refused_like_an_empty_box(): void {
		$response = $this->call( 'POST', array( '', '  ' ) );

		self::assertSame( 422, $response->get_status() );
		self::assertSame( 'URL is incorrect.', $response->get_data()['data']['fields']['urls'] );
	}

	public function test_an_author_may_not_start_or_follow_a_run(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'author' ) ) );

		self::assertSame( 403, $this->call( 'POST', array( 'https://www.example.com/c' ) )->get_status() );
		self::assertSame( 403, $this->call( 'GET' )->get_status() );
	}
}
