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

	/** @var list<list<array{0: string, 1: string}>> Each prime() call's keys. */
	public array $primed = array();

	public function prime( array $keys ): void {
		$this->primed[] = $keys;
	}

	public function find( string $asin, string $country ): ?AmazonProduct {
		return $this->rows[ $asin . '|' . $country ] ?? null;
	}

	public function touch( string $asin, string $country, string $now ): void {
		$row = $this->find( $asin, $country );
		if ( null !== $row ) {
			$this->rows[ $asin . '|' . $country ] = new AmazonProduct( $row->asin, $row->country, $row->name, $row->price, $row->currency, $row->base_url, $row->monetized_url, $row->image, $row->out_of_stock, $row->savings_amount, $row->savings_percent, $row->savings_basis, $row->features, $row->source, $now, $row->is_prime, $row->price_amount, $row->basis_amount );
		}
	}

	public function save( AmazonProduct $product ): void {
		$this->rows[ $product->asin . '|' . $product->country ] = $product->over( $this->find( $product->asin, $product->country ) );
	}

	/**
	 * An Amazon data service with no credentials and no stored data: a new product keeps the name its link gave it, as
	 * before C1C. For the ProductService tests that are not about Amazon's data.
	 */
	public static function without_amazon(): AmazonDataService {
		return new AmazonDataService( new InMemoryCredentials(), new FakeAmazonCatalog(), new self(), new InMemoryProductRepository() );
	}
}
