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
use Affibloom\Domain\Amazon\SearchPage;
use Closure;

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

	/** The catalogue's search request (R68). */
	private const SEARCH_ITEMS = 'https://creatorsapi.amazon/catalog/v1/searchItems';

	/** What searchItems is asked to return: Lasso's SEARCH_RESOURCES (R68). */
	private const SEARCH_RESOURCES = array(
		'images.primary.large',
		'images.primary.medium',
		'images.primary.small',
		'itemInfo.title',
		'offersV2.listings.price',
		'offersV2.listings.availability',
	);

	/**
	 * What getItems is asked to return (C1Q2): Lasso's DEFAULT_RESOURCES (R9) and every other resource Affibloom reads.
	 * Left out, as nothing reads them: contentInfo and contentRating (books, media), technicalInfo, tradeInInfo and
	 * loyaltyPoints (Japan only).
	 */
	private const RESOURCES = array(
		'browseNodeInfo.browseNodes',
		'browseNodeInfo.browseNodes.ancestor',
		'browseNodeInfo.browseNodes.salesRank',
		'browseNodeInfo.websiteSalesRank',
		'images.primary.large',
		'images.primary.medium',
		'images.primary.small',
		'images.variants.large',
		'images.variants.medium',
		'images.variants.small',
		'itemInfo.byLineInfo',
		'itemInfo.classifications',
		'itemInfo.externalIds',
		'itemInfo.features',
		'itemInfo.manufactureInfo',
		'itemInfo.productInfo',
		'itemInfo.title',
		'offersV2.listings.availability',
		'offersV2.listings.condition',
		'offersV2.listings.dealDetails',
		'offersV2.listings.isBuyBoxWinner',
		'offersV2.listings.merchantInfo',
		'offersV2.listings.price',
		'offersV2.listings.type',
		'parentASIN',
	);

	/**
	 * Every getItems resource, as Amazon's Creators API reference lists them ("Resources Parameter", read 2026-11-15;
	 * C1Q1). `raw()` asks for them all when no list is given; the variation summary belongs to getVariations, not here.
	 */
	private const ALL_RESOURCES = array(
		'browseNodeInfo.browseNodes',
		'browseNodeInfo.browseNodes.ancestor',
		'browseNodeInfo.browseNodes.salesRank',
		'browseNodeInfo.websiteSalesRank',
		'images.primary.small',
		'images.primary.medium',
		'images.primary.large',
		'images.variants.small',
		'images.variants.medium',
		'images.variants.large',
		'itemInfo.byLineInfo',
		'itemInfo.classifications',
		'itemInfo.contentInfo',
		'itemInfo.contentRating',
		'itemInfo.externalIds',
		'itemInfo.features',
		'itemInfo.manufactureInfo',
		'itemInfo.productInfo',
		'itemInfo.technicalInfo',
		'itemInfo.title',
		'itemInfo.tradeInInfo',
		'offersV2.listings.availability',
		'offersV2.listings.condition',
		'offersV2.listings.dealDetails',
		'offersV2.listings.isBuyBoxWinner',
		'offersV2.listings.loyaltyPoints',
		'offersV2.listings.merchantInfo',
		'offersV2.listings.price',
		'offersV2.listings.type',
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
		$body  = $this->get_items_answer( $credentials, array( $asin ), $country );
		$items = $body['itemsResult']['items'] ?? array();
		if ( ! is_array( $items ) || ! isset( $items[0] ) || ! is_array( $items[0] ) ) {
			throw new CatalogError( CatalogError::NOT_FOUND, 'Product not found.' );
		}

		return CatalogItem::from_creators( $items[0] );
	}

	/**
	 * The getItems answer for one to ten ASINs (R9).
	 *
	 * @param Credentials $credentials Configured credentials.
	 * @param list<Asin>  $asins       The ASINs.
	 * @param string      $country     The store's country code.
	 * @return array<array-key, mixed>
	 * @throws CatalogError AuthFailed (no token), HttpError (no answer), Throttled (429), ApiError (an error answer).
	 */
	private function get_items_answer( Credentials $credentials, array $asins, string $country ): array {
		return $this->answer(
			$credentials,
			fn( string $token ) => $this->get_items( $credentials, $token, $asins, $country, self::RESOURCES ),
			'Creators API getItems request failed.'
		);
	}

	/**
	 * One page of a search (R68: Lasso's search_items_request, every category, ten items; the page added, D13). An
	 * answer without items is an empty page.
	 *
	 * @param Credentials $credentials Configured credentials.
	 * @param string      $keywords    What to search for.
	 * @param int         $page        The page, 1 to 10.
	 * @param string      $country     The store's country code.
	 * @throws CatalogError AuthFailed (no token), HttpError (no answer), Throttled (429), ApiError (an error answer).
	 */
	public function search( Credentials $credentials, string $keywords, int $page, string $country ): SearchPage {
		$marketplace = Marketplace::host( $country );
		$body        = $this->answer(
			$credentials,
			fn( string $token ) => $this->post(
				self::SEARCH_ITEMS,
				$credentials,
				$token,
				$marketplace,
				array(
					'keywords'    => $keywords,
					'searchIndex' => 'All',
					'itemCount'   => SearchPage::SIZE,
					'itemPage'    => $page,
					'marketplace' => $marketplace,
					'partnerTag'  => $credentials->partner_tag,
					'resources'   => self::SEARCH_RESOURCES,
				)
			),
			'Creators API searchItems request failed.'
		);

		$result = is_array( $body['searchResult'] ?? null ) ? $body['searchResult'] : array();
		$items  = array();
		foreach ( is_array( $result['items'] ?? null ) ? $result['items'] : array() as $item ) {
			if ( is_array( $item ) && is_string( $item['asin'] ?? null ) && '' !== $item['asin'] ) {
				$items[] = CatalogItem::from_creators( $item );
			}
		}
		$total = is_numeric( $result['totalResultCount'] ?? null ) ? (int) $result['totalResultCount'] : count( $items );

		return new SearchPage( $items, $total, $page );
	}

	/**
	 * A catalogue request's answer, the token first; the decoded answer, or the request's error (R13).
	 *
	 * @param Credentials                                       $credentials Configured credentials.
	 * @param Closure(string): (array<string, mixed>|\WP_Error) $send        The request, given the token.
	 * @param string                                            $failed      Lasso's message for an error answer that has none of Amazon's.
	 * @return array<array-key, mixed>
	 * @throws CatalogError AuthFailed (no token), HttpError (no answer), Throttled (429), ApiError (an error answer).
	 */
	private function answer( Credentials $credentials, Closure $send, string $failed ): array {
		try {
			$token = $this->token( $credentials );
		} catch ( CatalogError $error ) {
			// Lasso's get_items() reports any token failure as AuthFailed; a throttled token stays Throttled, so the
			// guard waits (C1Q6a).
			if ( CatalogError::THROTTLED === $error->kind ) {
				throw $error;
			}
			throw new CatalogError( CatalogError::AUTH_FAILED, 'Could not obtain Creators API access token.' );
		}

		$response = $send( $token );

		if ( is_wp_error( $response ) ) {
			throw new CatalogError( CatalogError::HTTP_ERROR, $response->get_error_message() );
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$body   = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( 429 === $status ) {
			// ThrottleException: Amazon's retryAfterSeconds, else Retry-After, else a minute (C1Q6a).
			$wait = is_array( $body ) && is_numeric( $body['retryAfterSeconds'] ?? null ) ? (int) $body['retryAfterSeconds'] : self::retry_after( $response, 60 );
			throw new CatalogError( CatalogError::THROTTLED, 'Request was denied due to request throttling.', '', $wait );
		}
		if ( $status >= 400 || ! is_array( $body ) ) {
			$message = is_array( $body ) && isset( $body['message'] ) && is_string( $body['message'] ) && '' !== $body['message'] ? $body['message'] : $failed;
			throw new CatalogError( CatalogError::API_ERROR, $message );
		}

		return $body;
	}

	/**
	 * Up to ten items of one store in one getItems request (C1Q6b). Per-item errors in a 200 answer (ItemNotAccessible)
	 * and ASINs Amazon leaves out are missing from the result; the request's own errors are thrown as item() throws them.
	 *
	 * @param Credentials $credentials Configured credentials.
	 * @param list<Asin>  $asins       One to ten ASINs.
	 * @param string      $country     The store's country code.
	 * @return array<string, CatalogItem> By ASIN, upper case.
	 * @throws CatalogError AuthFailed, HttpError, ApiError or Throttled.
	 */
	public function items( Credentials $credentials, array $asins, string $country ): array {
		$body  = $this->get_items_answer( $credentials, $asins, $country );
		$found = array();
		foreach ( is_array( $body['itemsResult']['items'] ?? null ) ? $body['itemsResult']['items'] : array() as $item ) {
			if ( is_array( $item ) && is_string( $item['asin'] ?? null ) && '' !== $item['asin'] ) {
				$found[ strtoupper( $item['asin'] ) ] = CatalogItem::from_creators( $item );
			}
		}

		return $found;
	}

	/**
	 * Amazon's getItems answer for one item exactly as received (C1Q1: `wp affibloom amazon get --raw`, for recording
	 * real answers as test fixtures): the HTTP status and the body, an error answer included.
	 *
	 * @param Credentials       $credentials Configured credentials.
	 * @param Asin              $asin        The item.
	 * @param string            $country     The store's country code.
	 * @param list<string>|null $resources   The resource paths to ask for; null asks every one (ALL_RESOURCES).
	 * @return array{status: int, body: string}
	 * @throws CatalogError AuthFailed (no token) or HttpError (no answer).
	 */
	public function raw( Credentials $credentials, Asin $asin, string $country, ?array $resources ): array {
		$response = $this->get_items( $credentials, $this->token( $credentials ), array( $asin ), $country, $resources ?? self::ALL_RESOURCES );
		if ( is_wp_error( $response ) ) {
			throw new CatalogError( CatalogError::HTTP_ERROR, $response->get_error_message() );
		}

		return array(
			'status' => (int) wp_remote_retrieve_response_code( $response ),
			'body'   => wp_remote_retrieve_body( $response ),
		);
	}

	/**
	 * POST getItems (R9): the token, up to ten ASINs, the store's marketplace, the partner tag and the resources.
	 *
	 * @param Credentials  $credentials Configured credentials.
	 * @param string       $token       A valid access token.
	 * @param list<Asin>   $asins       One to ten items.
	 * @param string       $country     The store's country code.
	 * @param list<string> $resources   The resource paths.
	 * @return array<string, mixed>|\WP_Error The response.
	 */
	private function get_items( Credentials $credentials, string $token, array $asins, string $country, array $resources ) {
		$marketplace = Marketplace::host( $country );

		return $this->post(
			self::GET_ITEMS,
			$credentials,
			$token,
			$marketplace,
			array(
				'itemIds'     => array_map( static fn( Asin $asin ): string => $asin->value, $asins ),
				'itemIdType'  => 'ASIN',
				'marketplace' => $marketplace,
				'partnerTag'  => $credentials->partner_tag,
				'resources'   => $resources,
			)
		);
	}

	/**
	 * POSTs a catalogue request as JSON with the token and the store's marketplace (R9).
	 *
	 * @param string               $url         The catalogue operation.
	 * @param Credentials          $credentials Configured credentials.
	 * @param string               $token       A valid access token.
	 * @param string               $marketplace The store's host.
	 * @param array<string, mixed> $body        The request.
	 * @return array<string, mixed>|\WP_Error The response.
	 */
	private function post( string $url, Credentials $credentials, string $token, string $marketplace, array $body ) {
		return wp_remote_post(
			$url,
			array(
				'timeout' => self::TIMEOUT,
				'headers' => array(
					'Authorization' => $credentials->authorization( $token ),
					'Content-Type'  => 'application/json',
					'x-marketplace' => $marketplace,
				),
				'body'    => (string) wp_json_encode( $body ),
			)
		);
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

		if ( 429 === $status ) {
			// Login with Amazon's too_many_requests: Retry-After, else Amazon's documented 300 seconds (C1Q6a).
			throw new CatalogError( CatalogError::THROTTLED, 'Too many token requests.', 'HTTP 429', self::retry_after( $response, 300 ) );
		}
		if ( '' === $token ) {
			// Lasso's verify_credentials(): no answer or 5xx is the service (429 is Throttled above); anything else is a
			// refusal (R21).
			$unavailable = 0 === $status || $status >= 500;
			// Amazon's own error code (Login with Amazon and Cognito answer {"error": "invalid_client", …}), kept as a
			// short reason for the card; only letters, digits and _ - . are kept.
			$code   = is_array( $body ) && isset( $body['error'] ) && is_string( $body['error'] ) ? substr( (string) preg_replace( '/[^A-Za-z0-9_.-]/', '', $body['error'] ), 0, 64 ) : '';
			$reason = "HTTP $status" . ( '' === $code ? '' : ", $code" );
			throw new CatalogError(
				$unavailable ? CatalogError::HTTP_ERROR : CatalogError::AUTH_FAILED,
				$unavailable ? "Service temporarily unavailable (HTTP $status)." : 'Could not obtain Creators API access token.',
				$reason
			);
		}

		$lifetime = isset( $body['expires_in'] ) && is_numeric( $body['expires_in'] ) ? (int) $body['expires_in'] : 3600;

		return array( $token, $lifetime );
	}

	/**
	 * A response's Retry-After in seconds, or the default.
	 *
	 * @param array<string, mixed> $response The response.
	 * @param int                  $fallback Seconds when it has none.
	 */
	private static function retry_after( array $response, int $fallback ): int {
		$header = wp_remote_retrieve_header( $response, 'retry-after' );

		return is_string( $header ) && is_numeric( $header ) ? max( 1, (int) $header ) : $fallback;
	}
}
