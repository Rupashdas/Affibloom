<?php
namespace Affibloom\Tests\Unit\Infrastructure;

use Affibloom\Application\Amazon\CatalogError;
use Affibloom\Domain\Amazon\Asin;
use Affibloom\Domain\Amazon\Credentials;
use Affibloom\Infrastructure\Http\CreatorsApiCatalog;
use Affibloom\Tests\Unit\TestCase;
use Brain\Monkey\Functions;

/**
 * The Creators API client (C1 R7-R9, R11, R13) against the recorded answers in tests/fixtures/amazon: what it sends,
 * how it caches the token, and how each answer becomes an item or a CatalogError.
 */
final class CreatorsApiCatalogTest extends TestCase {
	/** @var list<array{0: string, 1: array<string, mixed>}> Requests made, in order. */
	private array $sent = array();

	/** @var list<array{0: int, 1: string}|object> Answers to give, in order: [status, fixture or body] or a transport error. */
	private array $answers = array();

	/** @var array<string, mixed> */
	private array $transients = array();

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\when( 'get_transient' )->alias( fn( string $key ) => $this->transients[ $key ][0] ?? false );
		Functions\when( 'set_transient' )->alias(
			function ( string $key, $value, int $ttl ): bool {
				$this->transients[ $key ] = array( $value, $ttl );
				return true;
			}
		);
		Functions\when( 'wp_remote_post' )->alias(
			function ( string $url, array $args ) {
				$this->sent[] = array( $url, $args );
				return array_shift( $this->answers );
			}
		);
		Functions\when( 'is_wp_error' )->alias( static fn( $value ): bool => is_object( $value ) );
		Functions\when( 'wp_remote_retrieve_response_code' )->alias( static fn( array $response ): int => $response[0] );
		Functions\when( 'wp_remote_retrieve_body' )->alias( static fn( array $response ): string => $response[1] );
	}

	private static function fixture( string $file ): string {
		return strtr( (string) file_get_contents( dirname( __DIR__, 2 ) . '/fixtures/amazon/' . $file ), array( '{{ASIN}}' => 'B0STUBITEM', '{{MARKETPLACE}}' => 'www.amazon.co.uk' ) );
	}

	private static function credentials( string $version = '3.1' ): Credentials {
		return new Credentials( 'client', 'secret', 'vbvault-21', $version );
	}

	private function item( string $version = '3.1' ) {
		return ( new CreatorsApiCatalog() )->item( self::credentials( $version ), Asin::from( 'B0STUBITEM' ), 'gb' );
	}

	public function test_an_item_is_asked_for_with_one_asin_the_stores_marketplace_and_the_partner_tag(): void {
		$this->answers = array( array( 200, self::fixture( 'token.json' ) ), array( 200, self::fixture( 'getItems-ok.json' ) ) );

		$item = $this->item();

		self::assertSame( 'Mikasa V200W Official Game Volleyball', $item->title );
		self::assertSame( 'https://api.amazon.com/auth/o2/token', $this->sent[0][0] );
		self::assertSame( 'https://creatorsapi.amazon/catalog/v1/getItems', $this->sent[1][0] );
		$request = $this->sent[1][1];
		self::assertSame( 10, $request['timeout'] );
		self::assertSame( 'Bearer stub-access-token', $request['headers']['Authorization'] );
		self::assertSame( 'www.amazon.co.uk', $request['headers']['x-marketplace'] );
		$body = json_decode( $request['body'], true );
		self::assertSame( array( 'B0STUBITEM' ), $body['itemIds'] );
		self::assertSame( array( 'ASIN', 'www.amazon.co.uk', 'vbvault-21' ), array( $body['itemIdType'], $body['marketplace'], $body['partnerTag'] ) );
		self::assertContains( 'offersV2.listings.availability', $body['resources'] );
	}

	public function test_the_token_is_cached_for_its_lifetime_less_a_minute_and_reused(): void {
		$this->answers = array( array( 200, self::fixture( 'token.json' ) ), array( 200, self::fixture( 'getItems-ok.json' ) ), array( 200, self::fixture( 'getItems-oos.json' ) ) );

		$this->item();
		$second = $this->item();

		self::assertCount( 3, $this->sent, 'one token request for two items' );
		self::assertFalse( $second->availability->in_stock );
		$cached = array_values( $this->transients )[0];
		self::assertSame( array( 'stub-access-token', 3540 ), $cached );
		self::assertStringStartsWith( 'affibloom_amazon_token_', array_keys( $this->transients )[0] );
		self::assertStringNotContainsString( 'secret', array_keys( $this->transients )[0] );
	}

	public function test_a_cognito_version_sends_a_form_and_names_the_version_in_the_header(): void {
		$this->answers = array( array( 200, self::fixture( 'token.json' ) ), array( 200, self::fixture( 'getItems-ok.json' ) ) );

		$this->item( '2.2' );

		self::assertStringContainsString( 'eu-south-2.amazoncognito.com', $this->sent[0][0] );
		self::assertSame( 'creatorsapi/default', $this->sent[0][1]['body']['scope'] );
		self::assertSame( 'Bearer stub-access-token, Version 2.2', $this->sent[1][1]['headers']['Authorization'] );
	}

	public function test_no_items_is_not_found(): void {
		$this->answers = array( array( 200, self::fixture( 'token.json' ) ), array( 200, self::fixture( 'getItems-empty.json' ) ) );
		$this->assertFailsWith( CatalogError::NOT_FOUND, 'Product not found.' );
	}

	public function test_a_refused_token_names_amazons_status_and_error(): void {
		$this->answers = array( array( 401, self::fixture( 'token-401.json' ) ) );
		try {
			( new CreatorsApiCatalog() )->verify( new Credentials( 'id', 'secret', 'tag-20', '3.1' ) );
			self::fail( 'a refused token was accepted' );
		} catch ( CatalogError $error ) {
			self::assertSame( array( CatalogError::AUTH_FAILED, 'HTTP 401, invalid_client' ), array( $error->kind, $error->reason ) );
		}
	}

	public function test_a_refused_token_is_auth_failed(): void {
		$this->answers = array( array( 401, self::fixture( 'token-401.json' ) ) );
		$this->assertFailsWith( CatalogError::AUTH_FAILED, 'Could not obtain Creators API access token.' );
	}

	public function test_an_error_answer_keeps_amazons_message(): void {
		$this->answers = array( array( 200, self::fixture( 'token.json' ) ), array( 429, self::fixture( 'error-429.json' ) ) );
		$this->assertFailsWith( CatalogError::API_ERROR, 'Request was denied due to request throttling.' );
	}

	public function test_an_error_answer_without_a_message_gets_lassos(): void {
		$this->answers = array( array( 200, self::fixture( 'token.json' ) ), array( 500, 'not json' ) );
		$this->assertFailsWith( CatalogError::API_ERROR, 'Creators API getItems request failed.' );
	}

	public function test_no_answer_is_an_http_error(): void {
		$this->answers = array(
			array( 200, self::fixture( 'token.json' ) ),
			new class() {
				public function get_error_message(): string {
					return 'cURL error 28: Operation timed out';
				}
			},
		);
		$this->assertFailsWith( CatalogError::HTTP_ERROR, 'cURL error 28: Operation timed out' );
	}

	private function assertFailsWith( string $kind, string $message ): void {
		try {
			$this->item();
			self::fail( 'no error' );
		} catch ( CatalogError $error ) {
			self::assertSame( array( $kind, $message ), array( $error->kind, $error->getMessage() ) );
		}
	}
}
