<?php
namespace Affibloom\Tests\Unit\Infrastructure\Http;

use Affibloom\Infrastructure\Http\WpPageFetcher;
use Affibloom\Tests\Unit\TestCase;
use Brain\Monkey\Functions;

/**
 * Task 37A, finding S3: the links an editor adds, and the short links the Amazon import finds in posts, are fetched
 * on the server. Each hop (one call, in a loop) goes through wp_safe_remote_get(), which refuses private and local
 * addresses, so a link cannot make the site read its own network.
 */
final class WpPageFetcherTest extends TestCase {

	public function test_the_page_is_read_with_a_safe_request(): void {
		$page = array( 'response' => array( 'code' => 200 ), 'headers' => array(), 'body' => '<title>Item</title>' );
		Functions\expect( 'wp_remote_get' )->never();
		Functions\expect( 'wp_safe_remote_get' )->once()->with( 'https://www.example.com/item', \Mockery::type( 'array' ) )->andReturn( $page );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'wp_remote_retrieve_response_code' )->alias( static fn( array $r ): int => $r['response']['code'] );
		Functions\when( 'wp_remote_retrieve_header' )->justReturn( '' );
		Functions\when( 'wp_remote_retrieve_body' )->alias( static fn( array $r ): string => $r['body'] );

		$fetched = ( new WpPageFetcher() )->fetch( 'https://www.example.com/item' );

		self::assertNotNull( $fetched );
		self::assertSame( 200, $fetched->status );
		self::assertSame( '<title>Item</title>', $fetched->body );
	}
}
