<?php
namespace Affibloom\Tests\Unit\Domain\Amazon;

use Affibloom\Domain\Amazon\Credentials;
use PHPUnit\Framework\TestCase;

final class CredentialsTest extends TestCase {
	private function full( string $version = '3.1' ): Credentials {
		return new Credentials( 'id', 'secret', 'tag-20', $version );
	}

	public function test_missing_fields_are_named_in_lassos_order(): void {
		self::assertSame( Credentials::FIELDS, ( new Credentials() )->missing() );
		self::assertSame( array( 'version', 'partner_tag' ), ( new Credentials( 'id', 'secret' ) )->missing() );
		self::assertTrue( $this->full()->configured() );
	}

	public function test_posted_values_are_trimmed_and_non_text_is_empty(): void {
		$credentials = Credentials::from_array( array( 'credential_id' => '  id ', 'secret' => array( 'x' ), 'version' => '3.1' ) );
		self::assertSame( 'id', $credentials->credential_id );
		self::assertSame( '', $credentials->secret );
	}

	public function test_a_blank_credential_id_or_secret_keeps_the_stored_one(): void {
		$typed = ( new Credentials( '', '', 'new-20', '3.2' ) )->kept_from( $this->full() );
		self::assertSame( array( 'id', 'secret', 'new-20', '3.2' ), array( $typed->credential_id, $typed->secret, $typed->partner_tag, $typed->version ) );
	}

	public function test_the_signature_is_lassos_sha256_of_the_values_and_country(): void {
		$expected = hash( 'sha256', '{"credential_id":"id","secret":"secret","credential_version":"3.1","partner_tag":"tag-20","country":"us"}' );
		self::assertSame( $expected, $this->full()->signature( 'us' ) );
		self::assertTrue( $this->full()->verified_by( $expected, 'us' ) );
		self::assertFalse( $this->full()->verified_by( $expected, 'gb' ), 'another store needs validating again' );
		self::assertFalse( ( new Credentials( 'id' ) )->verified_by( $expected, 'us' ) );
		self::assertFalse( $this->full()->verified_by( '', 'us' ) );
	}

	public function test_token_endpoints_and_scopes_follow_the_version(): void {
		self::assertSame( 'https://api.amazon.com/auth/o2/token', $this->full( '3.1' )->token_endpoint() );
		self::assertSame( 'https://api.amazon.co.uk/auth/o2/token', $this->full( '3.2' )->token_endpoint() );
		self::assertSame( 'https://creatorsapi.auth.eu-south-2.amazoncognito.com/oauth2/token', $this->full( '2.2' )->token_endpoint() );
		self::assertSame( 'https://api.amazon.com/auth/o2/token', $this->full( '9.9' )->token_endpoint() );
		self::assertSame( 'creatorsapi::default', $this->full( '3.3' )->token_request()['scope'] );
		self::assertSame( 'creatorsapi/default', $this->full( '2.1' )->token_request()['scope'] );
		self::assertTrue( $this->full( '3.1' )->uses_login_with_amazon() );
		self::assertFalse( $this->full( '2.3' )->uses_login_with_amazon() );
	}
}
