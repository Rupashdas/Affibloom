<?php
/**
 * The Product Data API card: the credentials' state, Validate, and saving them with the Amazon tab.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Amazon;

use Affibloom\Domain\Amazon\Credentials;
use Affibloom\Domain\Amazon\CredentialsRepository;
use Affibloom\Domain\Amazon\CredentialsState;
use Affibloom\Domain\Amazon\Marketplace;
use Affibloom\Domain\Amazon\Refusal;
use Affibloom\Domain\Settings\SettingsRepository;

/**
 * The Product Data API card (C1 R19-R23, D2-D4): what it says about the stored credentials, Validate (Lasso's token
 * check, without Lasso's licence gate), and Save Changes, which validates credentials that are saved but not yet
 * validated. The Default Tracking ID country is an ordinary setting (`default_country`), because the signature and the
 * bare-ASIN rule both read it.
 */
final class CredentialsService {

	/** Save Changes kept unvalidated credentials because Amazon was unavailable (R23). */
	public const SAVE_KEPT = 'kept';

	/** Save Changes kept credentials Amazon refused, marked refused (R23; C1Q0: Lasso clears them). */
	public const SAVE_REFUSED = 'refused';

	/**
	 * Builds the service.
	 *
	 * @param CredentialsRepository $credentials Where the credentials are kept.
	 * @param AmazonCatalog         $catalog     Amazon, for the token check.
	 * @param SettingsRepository    $settings    The settings, for `default_country`.
	 */
	public function __construct(
		private CredentialsRepository $credentials,
		private AmazonCatalog $catalog,
		private SettingsRepository $settings
	) {}

	/**
	 * The stored credentials, for the card (it prints the Partner tag and Version, never the other two).
	 */
	public function stored(): Credentials {
		return $this->credentials->get();
	}

	/**
	 * Why and when Amazon refused the stored credentials, or null (C1Q0): the card and the command say it.
	 */
	public function refusal(): ?Refusal {
		return $this->credentials->refusal();
	}

	/**
	 * What the card says (D4): Not set up, Refused by Amazon (C1Q0), Saved (not validated), or Validated for the
	 * current country.
	 */
	public function state(): CredentialsState {
		$stored = $this->credentials->get();
		if ( ! $stored->configured() ) {
			return CredentialsState::NotSetUp;
		}
		if ( null !== $this->credentials->refusal() ) {
			return CredentialsState::Refused;
		}

		return $stored->verified_by( $this->credentials->signature(), $this->default_country() ) ? CredentialsState::Validated : CredentialsState::Saved;
	}

	/**
	 * Validate (R19-R22, D3): checks the typed values (a blank Credential ID or Secret means the stored one) by asking
	 * Amazon for a token. On success the four values and the country are saved with their signature, and nothing else.
	 * A rejection stores the typed values marked refused (C1Q0; Lasso clears them, R22; the country stays, D3); an
	 * unavailable service changes nothing.
	 *
	 * @param array<array-key, mixed> $input   The four typed values by field name.
	 * @param string                  $country The Default Tracking ID as selected; an unknown code keeps the saved one.
	 * @throws CredentialsNotValidated When fields are missing, Amazon refuses them, or Amazon is unavailable.
	 */
	public function validate( array $input, string $country ): CredentialsState {
		$typed   = Credentials::from_array( $input )->kept_from( $this->credentials->get() );
		$country = Marketplace::is_country( $country ) ? $country : $this->default_country();

		$missing = $typed->missing();
		if ( array() !== $missing ) {
			throw new CredentialsNotValidated( CredentialsNotValidated::MISSING, $missing );
		}

		$this->check( $typed );

		$this->credentials->save( $typed, $typed->signature( $country ) );
		if ( $country !== $this->default_country() ) {
			$this->settings->save( $this->settings->get()->with( array( 'default_country' => $country ) ) );
		}

		return CredentialsState::Validated;
	}

	/**
	 * Save Changes on the Amazon tab, after the tab's settings (the country among them) were saved: stores the four
	 * values (blank Credential ID or Secret keeps the stored one), then validates them when they are configured and
	 * not yet validated (R23).
	 *
	 * @param array<array-key, mixed> $input The four posted values by field name.
	 * @return string|null SAVE_KEPT or SAVE_REFUSED when the validation failed, else null.
	 */
	public function save( array $input ): ?string {
		$stored  = $this->credentials->get();
		$posted  = Credentials::from_array( $input )->kept_from( $stored );
		$country = $this->default_country();

		// Unchanged values keep their signature; it still says whether they are validated for this country.
		$signature = $posted == $stored ? $this->credentials->signature() : ''; // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual -- equal values, not the same object.
		$this->credentials->save( $posted, $signature );

		if ( ! $posted->configured() || $posted->verified_by( $signature, $country ) ) {
			return null;
		}

		try {
			$this->check( $posted );
		} catch ( CredentialsNotValidated $refused ) {
			return CredentialsNotValidated::UNAVAILABLE === $refused->reason ? self::SAVE_KEPT : self::SAVE_REFUSED;
		}
		$this->credentials->save( $posted, $posted->signature( $country ) );

		return null;
	}

	/**
	 * Asks Amazon for a token. A refusal keeps these credentials, marked refused with Amazon's reason and the time
	 * (C1Q0, deviation 119): Lasso clears them (R22), which lost validated credentials on the Local site on 2026-11-15
	 * with no trace of why (deviation 118).
	 *
	 * @param Credentials $credentials Configured credentials.
	 * @throws CredentialsNotValidated When Amazon refuses them or is unavailable.
	 */
	private function check( Credentials $credentials ): void {
		try {
			$this->catalog->verify( $credentials );
		} catch ( CatalogError $error ) {
			if ( $error->unavailable() ) {
				throw new CredentialsNotValidated( CredentialsNotValidated::UNAVAILABLE );
			}
			$this->credentials->refuse( $credentials, new Refusal( '' !== $error->reason ? $error->reason : $error->getMessage(), gmdate( 'Y-m-d H:i:s' ) ) );
			throw new CredentialsNotValidated( CredentialsNotValidated::REJECTED );
		}
	}

	/**
	 * The saved Default Tracking ID country: the store the catalogue command asks by default.
	 */
	public function default_country(): string {
		return $this->settings->get()->string( 'default_country' );
	}
}
