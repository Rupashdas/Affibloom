<?php
namespace Affibloom\Tests\Unit\Application\LinkHealth;

use Affibloom\Application\LinkHealth\CatalogProductCheck;
use Affibloom\Application\Amazon\CatalogError;
use Affibloom\Domain\Amazon\Availability;
use Affibloom\Domain\Amazon\CatalogItem;
use Affibloom\Domain\Amazon\Credentials;
use Affibloom\Domain\Amazon\Refusal;
use Affibloom\Domain\LinkHealth\CheckResult;
use Affibloom\Tests\Unit\Fakes\FakeAmazonCatalog;
use Affibloom\Tests\Unit\Fakes\InMemoryCredentials;
use PHPUnit\Framework\TestCase;

/**
 * An Amazon product's check through the product API (Task 30C; F11 R10, R16, D7, D8): in stock 200, no stock 000,
 * not found 404; an error the API gives about the item does not raise an alert (Lasso's ignore list); a service that
 * does not answer, or credentials Amazon refuses, check nothing.
 */
final class CatalogProductCheckTest extends TestCase {

	private FakeAmazonCatalog $catalog;

	private InMemoryCredentials $credentials;

	protected function setUp(): void {
		$this->catalog     = new FakeAmazonCatalog();
		$this->credentials = new InMemoryCredentials( new Credentials( 'id', 'secret', 'tag-20', '3.1' ) );
	}

	private function check(): CatalogProductCheck {
		return new CatalogProductCheck( $this->credentials, $this->catalog );
	}

	private function item( string $asin, string $availability ): CatalogItem {
		return new CatalogItem( $asin, 'Ball', '', '', '', '', 0, '', Availability::from_listing( $availability, '' ), array(), '' );
	}

	private static function result( CheckResult $result ): string {
		return ( $result->was_checked() ? 'checked ' : 'not checked ' ) . $result->type;
	}

	public function test_without_credentials_amazon_is_not_checked(): void {
		$this->credentials = new InMemoryCredentials();
		$this->assertFalse( $this->check()->is_configured() );

		$this->credentials = new InMemoryCredentials( new Credentials( 'id', 'secret', 'tag-20', '3.1' ) );
		$this->credentials->refusal = new Refusal( 'HTTP 401, invalid_client', '2026-11-18 03:00:00' );
		$this->assertFalse( $this->check()->is_configured() );
	}

	public function test_in_stock_is_200_and_no_stock_is_000(): void {
		$this->catalog->items['B0IN000001']  = $this->item( 'B0IN000001', 'IN_STOCK' );
		$this->catalog->items['B0OUT00001']  = $this->item( 'B0OUT00001', 'OUT_OF_STOCK' );

		$this->assertTrue( $this->check()->is_configured() );
		$this->assertSame( 'checked 200', self::result( $this->check()->status( 'B0IN000001', 'us' ) ) );
		$this->assertTrue( $this->check()->status( 'B0IN000001', 'us' )->is_ok() );
		$this->assertSame( 'checked 000', self::result( $this->check()->status( 'B0OUT00001', 'us' ) ) );
	}

	public function test_an_item_amazon_does_not_have_is_404_at_once(): void {
		$result = $this->check()->status( 'B0GONE0001', 'us' );

		$this->assertSame( 'checked 404', self::result( $result ) );
		$this->assertFalse( $result->needs_confirmation() );
	}

	public function test_a_service_that_does_not_answer_checks_nothing(): void {
		$this->catalog->failing = true;
		$this->assertSame( 'not checked ', self::result( $this->check()->status( 'B0IN000001', 'us' ) ) );

		$this->catalog->failing   = false;
		$this->catalog->throttled = 30;
		$this->assertSame( 'not checked ', self::result( $this->check()->status( 'B0IN000001', 'us' ) ) );
	}

	public function test_other_api_errors_do_not_alert_and_refused_credentials_check_nothing(): void {
		$this->catalog->item_error = CatalogError::API_ERROR;
		$this->assertSame( 'checked 200', self::result( $this->check()->status( 'B0IN000001', 'us' ) ) );

		$this->catalog->item_error = CatalogError::AUTH_FAILED;
		$this->assertSame( 'not checked ', self::result( $this->check()->status( 'B0IN000001', 'us' ) ) );
	}

	public function test_a_value_that_is_not_an_asin_is_404(): void {
		$this->assertSame( 'checked 404', self::result( $this->check()->status( 'not an asin!', 'us' ) ) );
	}
}
