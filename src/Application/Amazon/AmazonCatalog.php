<?php
/**
 * Amazon's Creators API, as the use cases need it.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Amazon;

use Affibloom\Domain\Amazon\Asin;
use Affibloom\Domain\Amazon\CatalogItem;
use Affibloom\Domain\Amazon\Credentials;
use Affibloom\Domain\Amazon\SearchPage;

/**
 * Amazon's Creators API, as the use cases need it (C1 D1: the only source of Amazon data; no cloud of Lasso's).
 */
interface AmazonCatalog {

	/**
	 * Validate's check (R21): asks Amazon for a new access token for the credentials, never a cached one.
	 *
	 * @param Credentials $credentials Configured credentials.
	 * @throws CatalogError When Amazon refuses them or does not answer.
	 */
	public function verify( Credentials $credentials ): void;

	/**
	 * One item from a store (R9, R11), one ASIN per request as Lasso.
	 *
	 * @param Credentials $credentials Configured credentials.
	 * @param Asin        $asin        The item.
	 * @param string      $country     The store's country code (Marketplace); an unknown one asks www.amazon.com.
	 * @throws CatalogError AuthFailed, HttpError, ApiError or NotFound.
	 */
	public function item( Credentials $credentials, Asin $asin, string $country ): CatalogItem;

	/**
	 * Up to ten items of one store in one request (C1Q6b; Amazon counts it once): the ones Amazon gave, by ASIN. An ASIN
	 * Amazon could not give (not found, ItemNotAccessible) is left out.
	 *
	 * @param Credentials $credentials Configured credentials.
	 * @param list<Asin>  $asins       One to ten ASINs.
	 * @param string      $country     The store's country code.
	 * @return array<string, CatalogItem>
	 * @throws CatalogError AuthFailed, HttpError, ApiError or Throttled for the whole request.
	 */
	public function items( Credentials $credentials, array $asins, string $country ): array;

	/**
	 * One page of a product search of a store (C1M; Lasso's search_items, R68): ten items a page, every category.
	 *
	 * @param Credentials $credentials Configured credentials.
	 * @param string      $keywords    What to search for, not empty.
	 * @param int         $page        The page, 1 to 10.
	 * @param string      $country     The store's country code.
	 * @throws CatalogError AuthFailed, HttpError, ApiError or Throttled.
	 */
	public function search( Credentials $credentials, string $keywords, int $page, string $country ): SearchPage;

	/**
	 * Amazon's answer for one item exactly as received (C1Q1, for recording real answers): status and body.
	 *
	 * @param Credentials       $credentials Configured credentials.
	 * @param Asin              $asin        The item.
	 * @param string            $country     The store's country code.
	 * @param list<string>|null $resources   The resource paths to ask for; null asks every one Amazon lists.
	 * @return array{status: int, body: string}
	 * @throws CatalogError AuthFailed or HttpError.
	 */
	public function raw( Credentials $credentials, Asin $asin, string $country, ?array $resources ): array;
}
