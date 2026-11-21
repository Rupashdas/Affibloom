<?php
namespace Affibloom\Tests\Unit\Application\Amazon;

use Affibloom\Application\Amazon\AmazonDataService;
use Affibloom\Application\Amazon\LinkHealth;
use Affibloom\Domain\Amazon\CatalogItem;
use Affibloom\Domain\Amazon\Credentials;
use Affibloom\Tests\Unit\Domain\Amazon\CatalogItemTest;
use Affibloom\Tests\Unit\Fakes\FakeAmazonCatalog;
use Affibloom\Tests\Unit\Fakes\InMemoryAmazonProducts;
use Affibloom\Tests\Unit\Fakes\InMemoryCredentials;
use Affibloom\Tests\Unit\Fakes\InMemoryProductRepository;
use PHPUnit\Framework\TestCase;

/**
 * Each fetch tells link health what Amazon said (C1 R55-R57, D11, AC13; Task C1H).
 */
final class AmazonLinkHealthHandOffTest extends TestCase {

	/** @var list<string> */
	private array $told = array();

	public function test_the_hourly_refresh_reports_in_stock_out_of_stock_and_not_found(): void {
		$amazon      = new FakeAmazonCatalog();
		$in          = CatalogItemTest::recorded( 'live-B000IXRCTQ-all.json' );
		$out         = $in;
		$in['asin']  = 'B0INSTOCK1';
		$out['asin'] = 'B0OUTSTOCK';
		$out['offersV2']['listings'][0]['availability']['type'] = 'OUT_OF_STOCK';
		$amazon->items['B0INSTOCK1']                            = CatalogItem::from_creators( $in );
		$amazon->items['B0OUTSTOCK']                            = CatalogItem::from_creators( $out );
		$test   = $this;
		$health = new class( $test ) implements LinkHealth {
			public function __construct( private AmazonLinkHealthHandOffTest $test ) {}

			public function found( string $asin, string $country, bool $in_stock ): void {
				$this->test->tell( "{$asin} {$country} " . ( $in_stock ? 'in' : 'out' ) );
			}

			public function not_found( string $asin, string $country ): void {
				$this->test->tell( "{$asin} {$country} gone" );
			}
		};
		$data   = new AmazonDataService( new InMemoryCredentials( new Credentials( 'id', 'secret', 'tag-20', '3.1' ) ), $amazon, new InMemoryAmazonProducts(), new InMemoryProductRepository(), $health );

		foreach ( array( 'B0INSTOCK1', 'B0OUTSTOCK', 'B0GONE0001' ) as $asin ) {
			self::assertTrue( $data->refresh_row( $asin, 'us', 'https://www.amazon.com/dp/' . $asin ) );
		}

		self::assertSame( array( 'B0INSTOCK1 us in', 'B0OUTSTOCK us out', 'B0GONE0001 us gone' ), $this->told );
	}

	public function tell( string $what ): void {
		$this->told[] = $what;
	}
}
