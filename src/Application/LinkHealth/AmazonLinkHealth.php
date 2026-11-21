<?php
/**
 * Amazon's answers filed with link health.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\LinkHealth;

use Affibloom\Application\Amazon\LinkHealth;
use Affibloom\Domain\LinkHealth\IssueType;
use Affibloom\Domain\LinkHealth\LinkIssueRepository;
use Affibloom\Domain\Product\ProductRepository;

/**
 * C1H's adapter to F11 (C1 D11; deviation 148): each product of the ASIN in that store gets the answer through
 * LinkCheckService::record(), as the daily pass would have filed it: out of stock `000`, not found `404`, in stock
 * `200` (which resolves an open issue). "Days before out of stock alerts" stays F11's filter.
 */
final class AmazonLinkHealth implements LinkHealth {

	/**
	 * Builds the adapter.
	 *
	 * @param LinkCheckService    $checks   Link health.
	 * @param LinkIssueRepository $issues   Which products have the ASIN.
	 * @param ProductRepository   $products Their destinations.
	 */
	public function __construct( private LinkCheckService $checks, private LinkIssueRepository $issues, private ProductRepository $products ) {}

	/**
	 * Amazon gave the item.
	 *
	 * @param string $asin     The ASIN.
	 * @param string $country  The store's country code.
	 * @param bool   $in_stock Whether Amazon can sell it now.
	 */
	public function found( string $asin, string $country, bool $in_stock ): void {
		$this->file( $asin, $country, $in_stock ? IssueType::OK : IssueType::OUT_OF_STOCK );
	}

	/**
	 * Amazon does not know the item.
	 *
	 * @param string $asin    The ASIN.
	 * @param string $country The store's country code.
	 */
	public function not_found( string $asin, string $country ): void {
		$this->file( $asin, $country, IssueType::NOT_FOUND );
	}

	/**
	 * Files the code for every published product of the ASIN in that store.
	 *
	 * @param string $asin       The ASIN.
	 * @param string $country    The store's country code.
	 * @param string $issue_type The code.
	 */
	private function file( string $asin, string $country, string $issue_type ): void {
		foreach ( $this->issues->with_asin( $asin, $country ) as $product_id ) {
			$product = $this->products->get( $product_id );
			if ( null !== $product ) {
				$this->checks->record( $product_id, $product->url->value(), $issue_type );
			}
		}
	}
}
