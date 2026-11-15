<?php
namespace Affibloom\Tests\Unit\Fakes;

use Affibloom\Application\Amazon\AmazonDataService;
use Affibloom\Domain\Amazon\AmazonProduct;
use Affibloom\Domain\Amazon\AmazonProductRepository;

/**
 * Amazon's data per ASIN and store, kept in memory, with the over-an-older-row rule the table applies.
 */
final class InMemoryAmazonProducts implements AmazonProductRepository {
	/** @var array<string, AmazonProduct> By "ASIN|country". */
	public array $rows = array();

	public function find( string $asin, string $country ): ?AmazonProduct {
		return $this->rows[ $asin . '|' . $country ] ?? null;
	}

	public function save( AmazonProduct $product ): void {
		$this->rows[ $product->asin . '|' . $product->country ] = $product->over( $this->find( $product->asin, $product->country ) );
	}

	/**
	 * An Amazon data service with no credentials and no stored data: a new product keeps the name its link gave it, as
	 * before C1C. For the ProductService tests that are not about Amazon's data.
	 */
	public static function without_amazon(): AmazonDataService {
		return new AmazonDataService( new InMemoryCredentials(), new FakeAmazonCatalog(), new self() );
	}
}
