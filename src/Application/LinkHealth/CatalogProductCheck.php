<?php
/**
 * An Amazon product's link status through C1's product API client.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\LinkHealth;

use Affibloom\Application\Amazon\AmazonCatalog;
use Affibloom\Application\Amazon\CatalogError;
use Affibloom\Domain\Amazon\Asin;
use Affibloom\Domain\Amazon\CredentialsRepository;
use Affibloom\Domain\LinkHealth\CheckResult;
use Affibloom\Domain\LinkHealth\IssueType;

/**
 * Lasso's check_amazon_link_status (F11 R16, D8) over C1's catalogue client (the guarded one, so the request counts
 * against the day's quota): the item in stock is 200, out of stock 000, not found (or not an ASIN) 404, all certain.
 * An error the catalogue gives about the item (Lasso's ignore list: ItemNotAccessible, InvalidParameterValue, …) does
 * not alert, as Lasso. Where Lasso's "other error → 200" would clear a real issue, a service that does not answer or
 * credentials Amazon refuses check nothing, so the last result stays (edge case 13).
 */
final class CatalogProductCheck implements AmazonProductCheck {

	/**
	 * Builds the check.
	 *
	 * @param CredentialsRepository $credentials The Creators API credentials.
	 * @param AmazonCatalog         $catalog     C1's catalogue client.
	 */
	public function __construct( private CredentialsRepository $credentials, private AmazonCatalog $catalog ) {}

	/**
	 * Whether the product API can be asked: all four credentials set and not refused by Amazon (C1 D4, C1Q0).
	 */
	public function is_configured(): bool {
		return $this->credentials->get()->configured() && null === $this->credentials->refusal();
	}

	/**
	 * What the product API says about the item.
	 *
	 * @param string $asin    The ASIN.
	 * @param string $country The store's country code.
	 */
	public function status( string $asin, string $country ): CheckResult {
		if ( ! $this->is_configured() ) {
			return CheckResult::not_executed();
		}
		$item_asin = Asin::from( $asin );
		if ( null === $item_asin ) {
			return CheckResult::certain( IssueType::NOT_FOUND );
		}
		try {
			$item = $this->catalog->item( $this->credentials->get(), $item_asin, $country );
		} catch ( CatalogError $error ) {
			return match ( $error->kind ) {
				CatalogError::NOT_FOUND => CheckResult::certain( IssueType::NOT_FOUND ),
				CatalogError::API_ERROR => CheckResult::certain( IssueType::OK ),
				default                 => CheckResult::not_executed(),
			};
		}

		return CheckResult::certain( $item->availability->in_stock ? IssueType::OK : IssueType::OUT_OF_STOCK );
	}
}
