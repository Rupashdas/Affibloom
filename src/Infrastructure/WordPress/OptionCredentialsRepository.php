<?php
/**
 * The Creators API credentials in their own option, not autoloaded.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Domain\Amazon\Credentials;
use Affibloom\Domain\Amazon\CredentialsRepository;

/**
 * The Creators API credentials in their own option, `affibloom_amazon_credentials` (C1 D2): not autoloaded, so the
 * secret is read only where Amazon is asked, and kept out of `affibloom_settings`, which REST, exports and the Lasso
 * import read.
 */
final class OptionCredentialsRepository implements CredentialsRepository {

	public const OPTION = 'affibloom_amazon_credentials';

	/**
	 * The stored credentials; empty ones when nothing is stored.
	 */
	public function get(): Credentials {
		return Credentials::from_array( $this->stored() );
	}

	/**
	 * The stored verification signature, or ''.
	 */
	public function signature(): string {
		$signature = $this->stored()['verified_signature'] ?? '';

		return is_string( $signature ) ? $signature : '';
	}

	/**
	 * Replaces the credentials and the signature.
	 *
	 * @param Credentials $credentials The credentials.
	 * @param string      $signature   Their signature when Amazon accepted them, else ''.
	 */
	public function save( Credentials $credentials, string $signature ): void {
		update_option(
			self::OPTION,
			array(
				'credential_id'      => $credentials->credential_id,
				'secret'             => $credentials->secret,
				'partner_tag'        => $credentials->partner_tag,
				'version'            => $credentials->version,
				'verified_signature' => $signature,
			),
			false
		);
	}

	/**
	 * The option as stored.
	 *
	 * @return array<array-key, mixed>
	 */
	private function stored(): array {
		$value = get_option( self::OPTION, array() );

		return is_array( $value ) ? $value : array();
	}
}
