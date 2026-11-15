<?php
/**
 * The Amazon Creators API credentials: Credential ID, Secret, Partner tag and Version.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Amazon;

/**
 * The Amazon Creators API credentials (C1 R2, R7, R18; D2). The Secret and the Credential ID are never shown: the card
 * prints them empty, and a blank field there means "keep the stored value" (kept_from()).
 */
final class Credentials {

	/** The fields in the order Lasso names missing ones (R20): Credential ID, Secret, Version, Partner tag. */
	public const FIELDS = array( 'credential_id', 'secret', 'version', 'partner_tag' );

	/** Lasso's token endpoints by Version (R7); any other Version uses 3.1's. */
	private const TOKEN_ENDPOINTS = array(
		'2.1' => 'https://creatorsapi.auth.us-east-1.amazoncognito.com/oauth2/token',
		'2.2' => 'https://creatorsapi.auth.eu-south-2.amazoncognito.com/oauth2/token',
		'2.3' => 'https://creatorsapi.auth.us-west-2.amazoncognito.com/oauth2/token',
		'3.1' => 'https://api.amazon.com/auth/o2/token',
		'3.2' => 'https://api.amazon.co.uk/auth/o2/token',
		'3.3' => 'https://api.amazon.co.jp/auth/o2/token',
	);

	/**
	 * Builds credentials (from_array() trims posted and stored values).
	 *
	 * @param string $credential_id Credential ID (the client id).
	 * @param string $secret        Secret (the client secret).
	 * @param string $partner_tag   Partner tag, an Associates tracking ID.
	 * @param string $version       Credential version ("3.1").
	 */
	public function __construct(
		public readonly string $credential_id = '',
		public readonly string $secret = '',
		public readonly string $partner_tag = '',
		public readonly string $version = ''
	) {}

	/**
	 * Credentials from stored or posted values; anything that is not text counts as empty.
	 *
	 * @param array<array-key, mixed> $values Values by field name.
	 */
	public static function from_array( array $values ): self {
		$text = static fn( string $key ): string => isset( $values[ $key ] ) && is_string( $values[ $key ] ) ? trim( $values[ $key ] ) : '';

		return new self( $text( 'credential_id' ), $text( 'secret' ), $text( 'partner_tag' ), $text( 'version' ) );
	}

	/**
	 * These credentials with a blank Credential ID or Secret taken from the stored ones (D2: the card prints those two
	 * empty, so leaving them blank keeps what is saved).
	 *
	 * @param Credentials $stored What is saved.
	 */
	public function kept_from( Credentials $stored ): self {
		return new self(
			'' === $this->credential_id ? $stored->credential_id : $this->credential_id,
			'' === $this->secret ? $stored->secret : $this->secret,
			$this->partner_tag,
			$this->version
		);
	}

	/**
	 * The fields that are empty, in Lasso's order (R20).
	 *
	 * @return list<string> Field names from FIELDS.
	 */
	public function missing(): array {
		return array_values( array_filter( self::FIELDS, fn( string $field ): bool => '' === $this->{$field} ) );
	}

	/**
	 * Lasso's "configured": all four fields set (R2). Lasso uses configured credentials even before they are verified
	 * (R1, D4).
	 */
	public function configured(): bool {
		return array() === $this->missing();
	}

	/**
	 * Lasso's verification signature (R2): sha256 of the four values and the store's country, as JSON. A stored
	 * signature equal to this one means Amazon granted a token for exactly these values.
	 *
	 * @param string $country The Default Tracking ID country.
	 */
	public function signature( string $country ): string {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Domain code does not call WordPress.
		$json = (string) json_encode(
			array(
				'credential_id'      => $this->credential_id,
				'secret'             => $this->secret,
				'credential_version' => $this->version,
				'partner_tag'        => $this->partner_tag,
				'country'            => $country,
			)
		);

		return hash( 'sha256', $json );
	}

	/**
	 * Whether a stored signature confirms these credentials for a country (R2, compared in constant time).
	 *
	 * @param string $signature The stored signature.
	 * @param string $country   The Default Tracking ID country.
	 */
	public function verified_by( string $signature, string $country ): bool {
		return $this->configured() && '' !== $signature && hash_equals( $signature, $this->signature( $country ) );
	}

	/**
	 * Whether this Version signs in with Login with Amazon (3.x, a JSON request) rather than Cognito (2.x, a form).
	 */
	public function uses_login_with_amazon(): bool {
		return str_starts_with( $this->version, '3.' );
	}

	/**
	 * Where this Version asks for a token (R7).
	 */
	public function token_endpoint(): string {
		return self::TOKEN_ENDPOINTS[ $this->version ] ?? self::TOKEN_ENDPOINTS['3.1'];
	}

	/**
	 * The token request's fields (R7): client credentials with the scope of the sign-in kind.
	 *
	 * @return array{grant_type: string, client_id: string, client_secret: string, scope: string}
	 */
	public function token_request(): array {
		return array(
			'grant_type'    => 'client_credentials',
			'client_id'     => $this->credential_id,
			'client_secret' => $this->secret,
			'scope'         => $this->uses_login_with_amazon() ? 'creatorsapi::default' : 'creatorsapi/default',
		);
	}

	/**
	 * What the token cache is keyed on (R8: client id, version and secret, so a changed secret never reuses a token),
	 * hashed so the key holds no secret.
	 */
	public function cache_key(): string {
		return md5( $this->credential_id . '|' . $this->version . '|' . $this->secret );
	}

	/**
	 * The catalogue's Authorization header for a token (R9): Login with Amazon takes the token alone, Cognito also the
	 * Version.
	 *
	 * @param string $token An access token.
	 */
	public function authorization( string $token ): string {
		return $this->uses_login_with_amazon() ? 'Bearer ' . $token : 'Bearer ' . $token . ', Version ' . $this->version;
	}
}
