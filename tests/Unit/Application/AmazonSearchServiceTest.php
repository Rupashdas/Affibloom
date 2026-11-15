<?php
namespace Affibloom\Tests\Unit\Application;

use Affibloom\Application\Amazon\AmazonDataService;
use Affibloom\Application\Amazon\AmazonSearchService;
use Affibloom\Application\Amazon\SearchRefused;
use Affibloom\Domain\Amazon\AmazonProduct;
use Affibloom\Domain\Amazon\Asin;
use Affibloom\Domain\Amazon\CatalogItem;
use Affibloom\Domain\Amazon\Credentials;
use Affibloom\Domain\Amazon\SearchPage;
use Affibloom\Tests\Unit\Domain\Amazon\CatalogItemTest;
use Affibloom\Tests\Unit\Fakes\FakeAmazonCatalog;
use Affibloom\Tests\Unit\Fakes\InMemoryAmazonProducts;
use Affibloom\Tests\Unit\Fakes\InMemoryCredentials;
use Affibloom\Tests\Unit\Fakes\InMemoryProductRepository;
use Affibloom\Tests\Unit\Fakes\InMemorySearchResults;
use Affibloom\Tests\Unit\Fakes\InMemorySettingsRepository;
use PHPUnit\Framework\TestCase;

/**
 * The Amazon tab of Add A New Link (C1M; R68, R71, D13; AC15): a search of the default store, ten a page, and a
 * selection that fills the stored data from the search result, so the add asks Amazon nothing.
 */
final class AmazonSearchServiceTest extends TestCase {
	private FakeAmazonCatalog $amazon;
	private InMemoryAmazonProducts $rows;

	private function service( bool $configured = true, string $country = 'gb' ): AmazonSearchService {
		$this->amazon = new FakeAmazonCatalog();
		$this->rows   = new InMemoryAmazonProducts();
		$credentials  = new InMemoryCredentials( $configured ? new Credentials( 'id', 'secret', 'tag-20', '3.1' ) : new Credentials() );
		$data         = new AmazonDataService( $credentials, $this->amazon, $this->rows, new InMemoryProductRepository() );

		return new AmazonSearchService( $data, $this->amazon, new InMemorySettingsRepository( array( 'default_country' => $country ) ), new InMemorySearchResults() );
	}

	private static function found( string $asin, string $title ): CatalogItem {
		$item                                  = CatalogItemTest::recorded( 'getItems-ok.json' );
		$item['asin']                          = $asin;
		$item['itemInfo']['title']['displayValue'] = $title;
		unset( $item['itemInfo']['features'] );

		return CatalogItem::from_creators( $item );
	}

	public function test_a_search_asks_the_default_store_for_the_page_and_says_whether_more_follow(): void {
		$service              = $this->service();
		$this->amazon->search = new SearchPage( array( self::found( 'B0SEARCH01', 'Ball' ) ), 30, 1 );

		$page = $service->search( 7, '  volleyball ', 2 );

		self::assertSame( array( array( 'volleyball', 2, 'gb' ) ), $this->amazon->searched );
		self::assertSame( 'Ball', $page->items[0]->title );
		self::assertTrue( $page->has_more() );
	}

	public function test_amazons_last_page_is_the_tenth(): void {
		self::assertFalse( ( new SearchPage( array(), 500, 10 ) )->has_more() );
		self::assertFalse( ( new SearchPage( array(), 30, 3 ) )->has_more() );
		self::assertTrue( ( new SearchPage( array(), 31, 3 ) )->has_more() );
	}

	public function test_without_credentials_nothing_is_asked(): void {
		$service = $this->service( false );

		try {
			$service->search( 7, 'volleyball', 1 );
			self::fail( 'searched without credentials' );
		} catch ( SearchRefused $refused ) {
			self::assertSame( SearchRefused::NOT_CONFIGURED, $refused->reason );
		}
		self::assertSame( array(), $this->amazon->searched );
	}

	public function test_empty_keywords_answer_an_empty_page_without_asking_amazon(): void {
		$service = $this->service();

		$page = $service->search( 7, ' ', 1 );

		self::assertSame( array(), $page->items );
		self::assertSame( array(), $this->amazon->searched );
	}

	public function test_an_unavailable_amazon_and_a_failed_search_are_refused_apart(): void {
		$service                = $this->service();
		$this->amazon->failing  = true;
		try {
			$service->search( 7, 'ball', 1 );
			self::fail( 'no refusal' );
		} catch ( SearchRefused $refused ) {
			self::assertSame( SearchRefused::UNAVAILABLE, $refused->reason );
		}

		$this->amazon->failing   = false;
		$this->amazon->api_error = true;
		try {
			$service->search( 7, 'ball', 1 );
			self::fail( 'no refusal' );
		} catch ( SearchRefused $refused ) {
			self::assertSame( SearchRefused::FAILED, $refused->reason );
		}
	}

	public function test_a_selection_stores_the_search_result_and_gives_the_stores_product_link(): void {
		$service              = $this->service();
		$this->amazon->search = new SearchPage( array( self::found( 'B0SEARCH01', 'Ball' ), self::found( 'B0SEARCH02', 'Net' ) ), 2, 1 );
		$service->search( 7, 'ball', 1 );

		$urls = $service->selected( 7, array( 'b0search02' ) );

		self::assertSame( array( 'https://www.amazon.co.uk/dp/B0SEARCH02' ), $urls );
		$row = $this->rows->find( 'B0SEARCH02', 'gb' );
		self::assertNotNull( $row );
		self::assertSame( array( 'Net', '$69.95', 'https://m.media-amazon.com/images/I/71stubLarge.jpg' ), array( $row->name, $row->price, $row->image ) );
		self::assertNull( $this->rows->find( 'B0SEARCH01', 'gb' ), 'only what was selected is stored' );
		self::assertSame( 0, $this->amazon->asked );
	}

	public function test_results_from_earlier_pages_can_still_be_selected(): void {
		$service              = $this->service();
		$this->amazon->search = new SearchPage( array( self::found( 'B0SEARCH01', 'Ball' ) ), 20, 1 );
		$service->search( 7, 'ball', 1 );
		$this->amazon->search = new SearchPage( array( self::found( 'B0SEARCH11', 'Knee pads' ) ), 20, 2 );
		$service->search( 7, 'ball', 2 );

		self::assertCount( 2, $service->selected( 7, array( 'B0SEARCH01', 'B0SEARCH11' ) ) );
	}

	public function test_stored_data_is_kept_over_the_search_result(): void {
		$service = $this->service();
		$older   = AmazonProduct::from_item( self::found( 'B0SEARCH01', 'Full Name' ), Asin::from( 'B0SEARCH01' ), 'gb', 'https://www.amazon.co.uk/dp/B0SEARCH01?tag=t-21', '2026-11-01 00:00:00' );
		$this->rows->save( $older );
		$this->amazon->search = new SearchPage( array( self::found( 'B0SEARCH01', 'Short' ) ), 1, 1 );
		$service->search( 7, 'ball', 1 );

		$service->selected( 7, array( 'B0SEARCH01' ) );

		self::assertSame( 'Full Name', $this->rows->find( 'B0SEARCH01', 'gb' )->name );
	}

	public function test_an_asin_not_in_this_persons_results_is_refused(): void {
		$service              = $this->service();
		$this->amazon->search = new SearchPage( array( self::found( 'B0SEARCH01', 'Ball' ) ), 1, 1 );
		$service->search( 7, 'ball', 1 );

		foreach ( array( array( 8, 'B0SEARCH01' ), array( 7, 'B0OTHER001' ), array( 7, 'not an asin' ) ) as [ $user, $asin ] ) {
			try {
				$service->selected( $user, array( $asin ) );
				self::fail( "$user $asin was accepted" );
			} catch ( SearchRefused $refused ) {
				self::assertSame( SearchRefused::INVALID, $refused->reason );
			}
		}
		self::assertSame( array(), $this->rows->rows );
	}
}
