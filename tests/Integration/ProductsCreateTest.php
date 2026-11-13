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
		self::assertSame( 'Mizunousa', $data['name'] ); // the embedded destination's site (Task 11D)
		self::assertSame( 'affibloom_product', get_post_type( $data['id'] ) );
		self::assertSame( $url, get_post_meta( $data['id'], '_affibloom_url', true ) );
		self::assertSame( 'other', get_post_meta( $data['id'], '_affibloom_type', true ) );
		self::assertStringContainsString( 'affibloom_notice=link-added', $data['redirect_to'] );
	}

	public function test_a_new_product_takes_the_sites_link_defaults_at_that_moment(): void {
		update_option( 'affibloom_settings', array( 'nofollow' => false, 'sponsored' => true ) );
		$id = $this->post( 'https://www.example.com/defaults' )->get_data()['data']['id'];

		self::assertSame( '0', get_post_meta( $id, '_affibloom_nofollow', true ) );
		self::assertSame( '1', get_post_meta( $id, '_affibloom_sponsored', true ) );
		self::assertSame( '1', get_post_meta( $id, '_affibloom_new_tab', true ) );
		self::assertSame( '1', get_post_meta( $id, '_affibloom_show_disclosure', true ) );

		update_option( 'affibloom_settings', array( 'nofollow' => true ) );
		self::assertSame( '0', get_post_meta( $id, '_affibloom_nofollow', true ) );
	}

	public function test_an_amazon_product_link_is_stored_as_lasso_stores_it(): void {
		update_option( 'affibloom_settings', array( 'tracking_id' => 'vbvault-20', 'tracking_id_whitelist' => array( 'vbvault-20' ) ) );
		$data = $this->post( 'https://www.amazon.com/Mizuno-Wave/dp/B0C1234567/ref=sr_1_3?keywords=shoe&tag=someone-20&ref_=x' )->get_data()['data'];

		self::assertSame( 'https://www.amazon.com/Mizuno-Wave/dp/B0C1234567/ref=sr_1_3?tag=vbvault-20&ref_=x', get_post_meta( $data['id'], '_affibloom_url', true ) );
		self::assertSame( 'amazon', get_post_meta( $data['id'], '_affibloom_type', true ) );
		self::assertSame( 'B0C1234567', get_post_meta( $data['id'], '_affibloom_asin', true ) );
		self::assertSame( 'us', get_post_meta( $data['id'], '_affibloom_amazon_country', true ) );
	}

	public function test_the_same_asin_on_the_same_store_is_the_product_that_exists(): void {
		update_option( 'affibloom_settings', array( 'tracking_id' => 'vbvault-20' ) );
		$first = $this->post( 'https://www.amazon.com/dp/B0C7654321' )->get_data()['data']['id'];

		$again = $this->post( 'https://www.amazon.com/gp/product/b0c7654321?th=1' );
		self::assertSame( 200, $again->get_status() );
		self::assertSame( $first, $again->get_data()['data']['id'] );

		$uk = $this->post( 'https://www.amazon.co.uk/dp/B0C7654321' )->get_data()['data']['id'];
		self::assertNotSame( $first, $uk );

		wp_trash_post( $first );
		self::assertNotSame( $first, $this->post( 'https://www.amazon.com/gp/aw/d/B0C7654321' )->get_data()['data']['id'] );
	}

	public function test_a_search_page_is_kept_whole_and_found_again_by_its_keywords(): void {
		update_option( 'affibloom_settings', array( 'tracking_id' => 'vbvault-20' ) );
		$first = $this->post( 'https://www.amazon.com/s?k=nike+kd14&crid=X1&tag=someone-20' )->get_data()['data']['id'];

		self::assertSame( 'https://www.amazon.com/s?k=nike%20kd14&crid=X1&tag=vbvault-20', get_post_meta( $first, '_affibloom_url', true ) );
		self::assertSame( 'amazon', get_post_meta( $first, '_affibloom_type', true ) );
		self::assertSame( 'amazon.com|nike kd14', get_post_meta( $first, '_affibloom_search_key', true ) );
		self::assertSame( $first, $this->post( 'https://www.amazon.com/s/ref=x?k=NIKE%20KD14' )->get_data()['data']['id'] );
	}

	public function test_a_short_link_is_stored_as_the_product_link_it_opens(): void {
		update_option( 'affibloom_settings', array( 'tracking_id' => 'vbvault-20' ) );
		$asked = array();
		add_filter(
			'pre_http_request',
			static function ( $pre, $request, $url ) use ( &$asked ) {
				$asked[] = $url;
				return 'https://amzn.to/46fBGAJ' === $url
					? array(
						'headers'  => array( 'location' => 'https://www.amazon.com/Stabilizer/dp/B00XOUQ8PE?tag=x-20' ),
						'body'     => '',
						'response' => array( 'code' => 301, 'message' => 'Moved Permanently' ),
						'cookies'  => array(),
						'filename' => null,
					)
					: new \WP_Error( 'blocked', 'No other request in this test.' );
			},
			10,
			3
		);

		$id = $this->post( 'https://amzn.to/46fBGAJ' )->get_data()['data']['id'];

		self::assertSame( 'https://www.amazon.com/Stabilizer/dp/B00XOUQ8PE?tag=vbvault-20', get_post_meta( $id, '_affibloom_url', true ) );
		self::assertSame( 'B00XOUQ8PE', get_post_meta( $id, '_affibloom_asin', true ) );
		self::assertSame( array( 'https://amzn.to/46fBGAJ' ), $asked ); // the store page is never requested

		$kept = $this->post( 'https://amzn.to/unreachable' )->get_data()['data']['id'];
		self::assertSame( 'https://amzn.to/unreachable', get_post_meta( $kept, '_affibloom_url', true ) );
		self::assertSame( 'amazon', get_post_meta( $kept, '_affibloom_type', true ) );
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
