<?php
/**
 * Amazon's Creators API over WordPress's HTTP client.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\Http;

use Affibloom\Application\Amazon\AmazonCatalog;
use Affibloom\Application\Amazon\CatalogError;
use Affibloom\Domain\Amazon\Credentials;

/**
 * Amazon's Creators API over WordPress's HTTP client, as Lasso_Amazon_Creators_Api calls it (C1 R7-R9, R13, R21).
 * Nothing goes anywhere but Amazon's own hosts (D1).
 */
final class CreatorsApiCatalog implements AmazonCatalog {

	/** Lasso's timeout for every Creators request, in seconds (R9). */
	private const TIMEOUT = 10;

	/**
	 * Validate's check (R21): a new token request, as Lasso's verify_credentials().
	 *
	 * @param Credentials $credentials Configured credentials.
	 * @throws CatalogError AuthFailed when Amazon refuses them; HttpError when it does not answer, answers 5xx or 429.
	 */
	public function verify( Credentials $credentials ): void {
		$this->request_token( $credentials );
	}

	/**
	 * Asks the Version's token endpoint for a token (R7): Login with Amazon (3.x) takes JSON, Cognito (2.x) a form.
	 *
	 * @param Credentials $credentials Configured credentials.
	 * @return string The token.
	 * @throws CatalogError AuthFailed when Amazon refuses them; HttpError when it does not answer, answers 5xx or 429.
	 */
	private function request_token( Credentials $credentials ): string {
		$fields   = $credentials->token_request();
		$json     = $credentials->uses_login_with_amazon();
		$response = wp_remote_post(
			$credentials->token_endpoint(),
			array(
				'timeout' => self::TIMEOUT,
				'headers' => array( 'Content-Type' => $json ? 'application/json' : 'application/x-www-form-urlencoded' ),
				'body'    => $json ? (string) wp_json_encode( $fields ) : $fields,
			)
		);

		// The errors' details go to logs and the command line, never to a page (CatalogError), so they are not escaped.
		// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
		if ( is_wp_error( $response ) ) {
			throw new CatalogError( CatalogError::HTTP_ERROR, $response->get_error_message() );
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$body   = json_decode( wp_remote_retrieve_body( $response ), true );
		$token  = is_array( $body ) && isset( $body['access_token'] ) && is_string( $body['access_token'] ) ? $body['access_token'] : '';

		if ( '' === $token ) {
			// Lasso's verify_credentials(): no answer, 5xx or 429 is the service; anything else is a refusal (R21).
			$unavailable = 0 === $status || 429 === $status || $status >= 500;
			throw new CatalogError(
				$unavailable ? CatalogError::HTTP_ERROR : CatalogError::AUTH_FAILED,
				$unavailable ? "Service temporarily unavailable (HTTP $status)." : 'Could not obtain Creators API access token.'
			);
		}
		// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped

		return $token;
	}
}
