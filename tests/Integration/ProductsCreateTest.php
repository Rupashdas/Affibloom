<?php
namespace Affibloom\Tests\Integration;

use WP_REST_Request;
use WP_REST_Response;
use WP_UnitTestCase;

/**
 * POST /affibloom/v1/products (Task 11A). Runs only where wp-env is available (progress.md, deviation 10).
 */
final class ProductsCreateTest extends WP_UnitTestCase {
	private function post( string $url, string $role = 'administrator' ): WP_REST_Response {
		wp_set_current_user( self::factory()->user->create( array( 'role' => $role ) ) );
		$request = new WP_REST_Request( 'POST', '/affibloom/v1/products' );
		$request->set_body_params( array( 'url' => $url ) );
		return rest_get_server()->dispatch( $request );
	}

	public function test_an_administrator_adds_a_link_kept_exactly(): void {
		$url      = 'https://mizuno.pxf.io/c/123/456/789?u=https%3A%2F%2Fmizunousa.com%2Fwave';
		$response = $this->post( $url );

		self::assertSame( 201, $response->get_status() );
		$data = $response->get_data()['data'];
		self::assertSame( $url, $data['url'] );
		self::assertSame( 'Pxf', $data['name'] );
		self::assertSame( 'affibloom_product', get_post_type( $data['id'] ) );
		self::assertSame( $url, get_post_meta( $data['id'], '_affibloom_url', true ) );
		self::assertSame( 'other', get_post_meta( $data['id'], '_affibloom_type', true ) );
		self::assertStringContainsString( 'affibloom_notice=link-added', $data['redirect_to'] );
	}

	public function test_a_bare_domain_is_stored_with_https(): void {
		self::assertSame( 'https://mizunousa.com/wave', $this->post( 'mizunousa.com/wave' )->get_data()['data']['url'] );
	}

	public function test_the_same_link_again_returns_the_existing_product(): void {
		$first  = $this->post( 'https://www.example.com/a' )->get_data()['data']['id'];
		$second = $this->post( 'HTTPS://WWW.Example.com/A/' );

		self::assertSame( 200, $second->get_status() );
		self::assertTrue( $second->get_data()['data']['duplicate'] );
		self::assertSame( $first, $second->get_data()['data']['id'] );
		self::assertStringContainsString( 'affibloom_notice=duplicate', $second->get_data()['data']['redirect_to'] );
	}

	public function test_a_trashed_product_is_not_a_match(): void {
		$first = $this->post( 'https://www.example.com/b' )->get_data()['data']['id'];
		wp_trash_post( $first );

		self::assertSame( 201, $this->post( 'https://www.example.com/b' )->get_status() );
	}

	public function test_an_empty_box_is_refused_with_lassos_words(): void {
		$response = $this->post( '  ' );

		self::assertSame( 422, $response->get_status() );
		self::assertSame( 'affibloom_invalid_field', $response->get_data()['code'] );
		self::assertSame( 'URL is incorrect.', $response->get_data()['data']['fields']['url'] );
	}

	public function test_something_that_is_not_a_link_is_refused(): void {
		self::assertSame( 'This is not a valid link.', $this->post( 'not a link' )->get_data()['data']['fields']['url'] );
	}

	public function test_an_author_may_not_add_products(): void {
		self::assertSame( 403, $this->post( 'https://example.com/b', 'author' )->get_status() );
	}
}
