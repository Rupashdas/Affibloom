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
use Affibloom\Domain\Amazon\Asin;
use Affibloom\Domain\Amazon\CatalogItem;
use Affibloom\Domain\Amazon\Credentials;
use Affibloom\Domain\Amazon\Marketplace;

/**
 * Amazon's Creators API over WordPress's HTTP client, as Lasso_Amazon_Creators_Api calls it (C1 R7-R9, R11-R13, R21).
 * Nothing goes anywhere but Amazon's own hosts (D1).
 *
 * The errors' details go to logs and the command line, never to a page (CatalogError), so they are not escaped.
 * phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
 */
final class CreatorsApiCatalog implements AmazonCatalog {

	/** Lasso's timeout for every Creators request, in seconds (R9). */
	private const TIMEOUT = 10;

	/** The catalogue's item request (R9). */
	private const GET_ITEMS = 'https://creatorsapi.amazon/catalog/v1/getItems';

	/** What getItems is asked to return (R9; Lasso's DEFAULT_RESOURCES). */
	private const RESOURCES = array(
		'images.primary.large',
		'images.primary.medium',
		'images.primary.small',
		'itemInfo.title',
		'itemInfo.features',
		'offersV2.listings.price',
		'offersV2.listings.availability',
		'parentASIN',
	);

	/** The token cache's transient prefix (R8, D6). */
	private const TOKEN_CACHE = 'affibloom_amazon_token_';

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
	 * One item from a store (R9): POST getItems with one ASIN, the store's marketplace and the partner tag.
	 *
	 * @param Credentials $credentials Configured credentials.
	 * @param Asin        $asin        The item.
	 * @param string      $country     The store's country code.
	 * @throws CatalogError AuthFailed (no token), HttpError (no answer), ApiError (an error answer), NotFound (no item).
	 */
	public function item( Credentials $credentials, Asin $asin, string $country ): CatalogItem {
		try {
			$token = $this->token( $credentials );
		} catch ( CatalogError $error ) {
			// Lasso's get_items() reports any token failure as AuthFailed.
			throw new CatalogError( CatalogError::AUTH_FAILED, 'Could not obtain Creators API access token.' );
		}

		$marketplace = Marketplace::host( $country );
		$response    = wp_remote_post(
			self::GET_ITEMS,
			array(
				'timeout' => self::TIMEOUT,
				'headers' => array(
					'Authorization' => $credentials->authorization( $token ),
					'Content-Type'  => 'application/json',
					'x-marketplace' => $marketplace,
				),
				'body'    => (string) wp_json_encode(
					array(
						'itemIds'     => array( $asin->value ),
						'itemIdType'  => 'ASIN',
						'marketplace' => $marketplace,
						'partnerTag'  => $credentials->partner_tag,
						'resources'   => self::RESOURCES,
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new CatalogError( CatalogError::HTTP_ERROR, $response->get_error_message() );
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$body   = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( $status >= 400 || ! is_array( $body ) ) {
			$message = is_array( $body ) && isset( $body['message'] ) && is_string( $body['message'] ) && '' !== $body['message'] ? $body['message'] : 'Creators API getItems request failed.';
			throw new CatalogError( CatalogError::API_ERROR, $message );
		}

		$items = $body['itemsResult']['items'] ?? array();
		if ( ! is_array( $items ) || ! isset( $items[0] ) || ! is_array( $items[0] ) ) {
			throw new CatalogError( CatalogError::NOT_FOUND, 'Product not found.' );
		}

		return CatalogItem::from_creators( $items[0] );
	}

	/**
	 * An access token from the cache while it is fresh, else a new one kept for its lifetime less a minute, at least a
	 * minute (R8).
	 *
	 * @param Credentials $credentials Configured credentials.
	 * @throws CatalogError When Amazon gives no token.
	 */
	private function token( Credentials $credentials ): string {
		$cache  = self::TOKEN_CACHE . $credentials->cache_key();
		$cached = get_transient( $cache );
		if ( is_string( $cached ) && '' !== $cached ) {
			return $cached;
		}

		[ $token, $lifetime ] = $this->request_token( $credentials );
		set_transient( $cache, $token, max( 60, $lifetime - 60 ) );

		return $token;
	}

	/**
	 * Asks the Version's token endpoint for a token (R7): Login with Amazon (3.x) takes JSON, Cognito (2.x) a form.
	 *
	 * @param Credentials $credentials Configured credentials.
	 * @return array{0: string, 1: int} The token and its lifetime in seconds (Lasso's default 3600).
	 * @throws CatalogError AuthFailed when Amazon refuses them; HttpError when it does not answer, answers 5xx or 429.
	 */
	private function request_token( Credentials $credentials ): array {
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

		$lifetime = isset( $body['expires_in'] ) && is_numeric( $body['expires_in'] ) ? (int) $body['expires_in'] : 3600;

		return array( $token, $lifetime );
	}
}
