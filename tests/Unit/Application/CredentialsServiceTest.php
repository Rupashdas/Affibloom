<?php
namespace Affibloom\Tests\Unit\Application;

use Affibloom\Application\Amazon\CredentialsNotValidated;
use Affibloom\Application\Amazon\CredentialsService;
use Affibloom\Domain\Amazon\Credentials;
use Affibloom\Domain\Amazon\CredentialsState;
use Affibloom\Tests\Unit\Fakes\FakeAmazonCatalog;
use Affibloom\Tests\Unit\Fakes\InMemoryCredentials;
use Affibloom\Tests\Unit\Fakes\InMemorySettingsRepository;
use PHPUnit\Framework\TestCase;

final class CredentialsServiceTest extends TestCase {
	private const TYPED = array(
		'credential_id' => 'id',
		'secret'        => 'secret',
		'partner_tag'   => 'tag-20',
		'version'       => '3.1',
	);

	private InMemoryCredentials $store;
	private FakeAmazonCatalog $amazon;
	private InMemorySettingsRepository $settings;

	private function service( string $answer = 'ok', ?Credentials $stored = null, string $signature = '' ): CredentialsService {
		$this->store    = new InMemoryCredentials( $stored ?? new Credentials(), $signature );
		$this->amazon   = new FakeAmazonCatalog( $answer );
		$this->settings = new InMemorySettingsRepository( array( 'tracking_id' => 'vbvault-20' ) );
		return new CredentialsService( $this->store, $this->amazon, $this->settings );
	}

	private static function stored(): Credentials {
		return Credentials::from_array( self::TYPED );
	}

	public function test_the_state_follows_the_stored_values_and_signature(): void {
		self::assertSame( CredentialsState::NotSetUp, $this->service()->state() );
		self::assertSame( CredentialsState::Saved, $this->service( 'ok', self::stored() )->state() );
		self::assertSame( CredentialsState::Validated, $this->service( 'ok', self::stored(), self::stored()->signature( 'us' ) )->state() );
		self::assertSame( CredentialsState::Saved, $this->service( 'ok', self::stored(), self::stored()->signature( 'gb' ) )->state(), 'validated for another store' );
	}

	public function test_validate_names_the_missing_fields_without_asking_amazon(): void {
		$service = $this->service();
		try {
			$service->validate( array( 'credential_id' => 'id' ), 'us' );
			self::fail( 'missing fields were accepted' );
		} catch ( CredentialsNotValidated $refused ) {
			self::assertSame( CredentialsNotValidated::MISSING, $refused->reason );
			self::assertSame( array( 'secret', 'version', 'partner_tag' ), $refused->missing );
		}
		self::assertSame( array(), $this->amazon->verified );
	}

	public function test_a_success_saves_the_values_the_country_and_the_signature_and_nothing_else(): void {
		$service = $this->service();
		$before  = $this->settings->get()->to_array();

		self::assertSame( CredentialsState::Validated, $service->validate( self::TYPED, 'gb' ) );
		self::assertEquals( self::stored(), $this->store->stored );
		self::assertSame( self::stored()->signature( 'gb' ), $this->store->signature );
		self::assertSame( array_merge( $before, array( 'default_country' => 'gb' ) ), $this->settings->get()->to_array() );
		self::assertSame( CredentialsState::Validated, $service->state() );
	}

	public function test_a_blank_secret_validates_the_stored_one(): void {
		$service = $this->service( 'ok', self::stored() );
		$service->validate( array( 'secret' => '', 'credential_id' => '' ) + self::TYPED, 'us' );
		self::assertSame( 'secret', $this->amazon->verified[0]->secret );
	}

	public function test_a_rejection_keeps_the_typed_values_marked_refused_and_not_the_country(): void {
		$service = $this->service( 'reject', self::stored() );
		try {
			$service->validate( self::TYPED, 'gb' );
			self::fail( 'a refusal was accepted' );
		} catch ( CredentialsNotValidated $refused ) {
			self::assertSame( CredentialsNotValidated::REJECTED, $refused->reason );
		}
		// C1Q0 (deviation 119): kept, not cleared as Lasso's R22, so a refusal never loses what was entered.
		self::assertEquals( Credentials::from_array( self::TYPED ), $this->store->stored );
		self::assertSame( '', $this->store->signature );
		self::assertSame( 'HTTP 401, invalid_client', $this->store->refusal?->reason );
		self::assertSame( CredentialsState::Refused, $service->state() );
		self::assertSame( 'us', $this->settings->get()->string( 'default_country' ) );
	}

	public function test_save_changes_after_a_refusal_asks_again_and_a_success_lifts_it(): void {
		$service = $this->service( 'reject', self::stored() );
		self::assertSame( CredentialsService::SAVE_REFUSED, $service->save( self::TYPED ) );
		self::assertNotNull( $this->store->refusal );

		$this->amazon->answer = 'ok';
		self::assertNull( $service->save( self::TYPED ) );
		self::assertNull( $this->store->refusal );
		self::assertSame( CredentialsState::Validated, $service->state() );
	}

	public function test_an_unavailable_service_changes_nothing(): void {
		$service = $this->service( 'unavailable', self::stored() );
		try {
			$service->validate( array( 'partner_tag' => 'other-20' ) + self::TYPED, 'us' );
			self::fail( 'an unavailable service was accepted' );
		} catch ( CredentialsNotValidated $refused ) {
			self::assertSame( CredentialsNotValidated::UNAVAILABLE, $refused->reason );
		}
		self::assertEquals( self::stored(), $this->store->stored );
	}

	public function test_save_validates_new_credentials(): void {
		$service = $this->service();
		self::assertNull( $service->save( self::TYPED ) );
		self::assertSame( CredentialsState::Validated, $service->state() );
	}

	public function test_save_keeps_credentials_it_could_not_validate(): void {
		$service = $this->service( 'unavailable' );
		self::assertSame( CredentialsService::SAVE_KEPT, $service->save( self::TYPED ) );
		self::assertEquals( self::stored(), $this->store->stored );
		self::assertSame( CredentialsState::Saved, $service->state() );

		$service = $this->service( 'reject' );
		self::assertSame( CredentialsService::SAVE_REFUSED, $service->save( self::TYPED ) );
		self::assertEquals( self::stored(), $this->store->stored );
		self::assertSame( CredentialsState::Refused, $service->state() );
	}

	public function test_save_does_not_ask_amazon_again_for_validated_unchanged_credentials(): void {
		$service = $this->service( 'reject', self::stored(), self::stored()->signature( 'us' ) );
		self::assertNull( $service->save( array( 'credential_id' => '', 'secret' => '' ) + self::TYPED ) );
		self::assertSame( array(), $this->amazon->verified );
		self::assertSame( CredentialsState::Validated, $service->state() );
	}

	public function test_save_of_incomplete_credentials_stores_them_without_asking_amazon(): void {
		$service = $this->service();
		self::assertNull( $service->save( array( 'credential_id' => 'id' ) ) );
		self::assertSame( 'id', $this->store->stored->credential_id );
		self::assertSame( array(), $this->amazon->verified );
		self::assertSame( CredentialsState::NotSetUp, $service->state() );
	}
}
