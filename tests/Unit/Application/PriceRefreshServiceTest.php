<?php
namespace Affibloom\Tests\Unit\Application;

use Affibloom\Application\Amazon\AmazonDataService;
use Affibloom\Application\Amazon\GuardedCatalog;
use Affibloom\Domain\Amazon\RequestLog;
use Affibloom\Tests\Unit\Fakes\InMemoryRequestLedger;
use Affibloom\Application\Amazon\PriceRefreshService;
use Affibloom\Application\Products\BackgroundJob;
use Affibloom\Domain\Amazon\AmazonProduct;
use Affibloom\Domain\Amazon\CatalogItem;
use Affibloom\Domain\Amazon\Credentials;
use Affibloom\Domain\Amazon\DueAmazonData;
use Affibloom\Domain\Amazon\RefreshRun;
use Affibloom\Domain\Amazon\RefreshRunRepository;
use Affibloom\Tests\Unit\Domain\Amazon\CatalogItemTest;
use Affibloom\Tests\Unit\Fakes\FakeAmazonCatalog;
use Affibloom\Tests\Unit\Fakes\InMemoryAmazonProducts;
use Affibloom\Tests\Unit\Fakes\InMemoryCredentials;
use Affibloom\Tests\Unit\Fakes\InMemoryProductRepository;
use Affibloom\Tests\Unit\Fakes\InMemorySettingsRepository;
use PHPUnit\Framework\TestCase;

/**
 * The scheduled Amazon refresh (C1Q4b; Lasso's lasso_cron_update_amazon, R37-R39): which rows, how many, paced, and
 * what a failure leaves.
 */
final class PriceRefreshServiceTest extends TestCase {
	private FakeAmazonCatalog $amazon;
	private InMemoryAmazonProducts $rows;
	/** @var list<array{asin: string, country: string, link: string}> */
	private array $due = array();
	private ?RefreshRun $run = null;
	private int $dispatched = 0;
	/** @var list<int> */
	private array $paused = array();
	private float $now = 1000.0;
	private InMemoryRequestLedger $ledger;

	/** "Only update products with Locations" for the next service(). */
	private bool $locations_only = true;

	private function service( bool $on = true, bool $configured = true ): PriceRefreshService {
		$this->amazon = new FakeAmazonCatalog();
		$this->rows   = new InMemoryAmazonProducts();
		$this->ledger = new InMemoryRequestLedger();
		$credentials  = $configured ? new Credentials( 'id', 'secret', 'tag-20', '3.1' ) : new Credentials();
		$data         = new AmazonDataService( new InMemoryCredentials( $credentials ), $this->amazon, $this->rows, new InMemoryProductRepository() );
		$test         = $this;

		$due = new class( $test ) implements DueAmazonData {
			public function __construct( private PriceRefreshServiceTest $test ) {}
			public function count( string $before, bool $used_only = false ): int {
				return count( $this->test->due_rows( $used_only ) );
			}
			public function due( string $before, int $limit, bool $used_only = false ): array {
				return array_slice( $this->test->due_rows( $used_only ), 0, $limit );
			}
		};
		$runs = new class( $test ) implements RefreshRunRepository {
			public function __construct( private PriceRefreshServiceTest $test ) {}
			public function get(): ?RefreshRun {
				return $this->test->stored_run();
			}
			public function save( ?RefreshRun $run ): void {
				$this->test->keep( $run );
			}
		};
		$job = new class( $test ) implements BackgroundJob {
			public function __construct( private PriceRefreshServiceTest $test ) {}
			public function dispatch( int $delay = 0 ): void {
				$this->test->dispatched();
			}
		};

		return new PriceRefreshService(
			$due,
			$runs,
			$job,
			$data,
			new InMemorySettingsRepository( array( 'update_pricing_daily' => $on, 'pricing_locations_only' => $this->locations_only ) ),
			new GuardedCatalog( $this->amazon, $this->ledger, fn(): float => $this->now, static function ( float $seconds ): void {} ),
			fn(): float => $this->now,
			function ( int $seconds ): void {
				$this->paused[] = $seconds;
				$this->now     += $seconds;
			}
		);
	}

	/** @var list<bool> Whether each due query asked for products used in content only (Task C1E). */
	private array $used_only = array();

	/** @return list<array{asin: string, country: string, link: string}> */
	public function due_rows( bool $used_only = false ): array {
		$this->used_only[] = $used_only;
		return $this->due;
	}

	public function stored_run(): ?RefreshRun {
		return $this->run;
	}

	public function keep( ?RefreshRun $run ): void {
		$this->run = $run;
	}

	public function dispatched(): void {
		++$this->dispatched;
	}

	private function item( string $asin ): void {
		$item                         = CatalogItemTest::recorded( 'live-B000IXRCTQ-all.json' );
		$item['asin']                 = $asin;
		$this->amazon->items[ $asin ] = CatalogItem::from_creators( $item );
	}

	private function row( string $asin, string $price ): void {
		$this->rows->save( new AmazonProduct( $asin, 'us', 'Stored', $price, 'USD', 'b', 'm', 'https://m.media-amazon.com/x.jpg', false, '', 0, '', array(), 'creators', '2026-11-01 00:00:00' ) );
	}

	/** Task C1E (C1 R38): "Only update products with Locations" narrows the due query; off, every product is due. */
	public function test_only_products_with_locations_are_due_while_the_setting_is_on(): void {
		$this->service()->tick();
		$this->locations_only = false;
		$this->service()->tick();

		self::assertSame( array( true, true, false, false ), $this->used_only );
	}

	public function test_switched_off_or_without_credentials_nothing_is_asked(): void {
		foreach ( array( array( false, true ), array( true, false ) ) as [ $on, $configured ] ) {
			$service   = $this->service( $on, $configured );
			$this->due = array( array( 'asin' => 'B0AAAAAAAA', 'country' => 'us', 'link' => 'l' ) );
			$service->tick();
			self::assertNull( $this->run );
			self::assertSame( array(), $this->rows->rows );
		}
	}

	public function test_a_run_refreshes_the_due_rows_in_order_one_a_second_and_writes_only_the_stored_data(): void {
		$service = $this->service();
		foreach ( array( 'B0AAAAAAAA', 'B0BBBBBBBB' ) as $asin ) {
			$this->row( $asin, '$1.00' );
			$this->item( $asin );
			$this->due[] = array( 'asin' => $asin, 'country' => 'us', 'link' => "https://www.amazon.com/dp/$asin" );
		}

		$service->tick();

		self::assertSame( array( '$32.97', 32.97 ), array( $this->rows->find( 'B0AAAAAAAA', 'us' )?->price, $this->rows->find( 'B0AAAAAAAA', 'us' )?->price_amount ) );
		self::assertSame( '$32.97', $this->rows->find( 'B0BBBBBBBB', 'us' )?->price );
		self::assertSame( array( 1 ), $this->paused, 'a second between two items, none after the last' );
		self::assertNull( $this->run, 'the run ends with its queue' );
	}

	public function test_not_found_only_moves_the_rows_time_on_and_the_run_goes_on(): void {
		$service = $this->service();
		$this->row( 'B0NOTFOUND', '$5.00' );
		$this->row( 'B0BBBBBBBB', '$1.00' );
		$this->item( 'B0BBBBBBBB' );
		$this->due = array(
			array( 'asin' => 'B0NOTFOUND', 'country' => 'us', 'link' => 'l' ),
			array( 'asin' => 'B0BBBBBBBB', 'country' => 'us', 'link' => 'l' ),
		);

		$service->tick();

		$gone = $this->rows->find( 'B0NOTFOUND', 'us' );
		self::assertSame( '$5.00', $gone?->price, 'the last good data is kept' );
		self::assertNotSame( '2026-11-01 00:00:00', $gone?->last_updated, 'not asked again for 24 hours' );
		self::assertSame( '$32.97', $this->rows->find( 'B0BBBBBBBB', 'us' )?->price );
	}

	public function test_an_amazon_failure_ends_the_run_and_leaves_the_rows_as_they_were(): void {
		$service              = $this->service();
		$this->amazon->answer = 'unavailable';
		$this->amazon->failing = true;
		$this->row( 'B0AAAAAAAA', '$1.00' );
		$this->due = array(
			array( 'asin' => 'B0AAAAAAAA', 'country' => 'us', 'link' => 'l' ),
			array( 'asin' => 'B0BBBBBBBB', 'country' => 'us', 'link' => 'l' ),
		);

		$service->tick();

		self::assertSame( array( '$1.00', '2026-11-01 00:00:00' ), array( $this->rows->find( 'B0AAAAAAAA', 'us' )?->price, $this->rows->find( 'B0AAAAAAAA', 'us' )?->last_updated ) );
		self::assertNull( $this->run );
		self::assertSame( 0, $this->dispatched );
	}

	public function test_the_batch_is_lassos_and_a_long_run_continues_in_another_pass(): void {
		$service = $this->service();
		for ( $i = 0; $i < 130; $i++ ) {
			$asin = sprintf( 'B0%08d', $i );
			$this->item( $asin );
			$this->due[] = array( 'asin' => $asin, 'country' => 'us', 'link' => 'l' );
		}

		$service->tick();

		// 130 due: a batch of 100; a pass stops after 20 seconds (one item a second) and wakes the next.
		self::assertSame( 1, $this->dispatched );
		self::assertNotNull( $this->run );
		self::assertSame( 100 - 20, count( $this->run->queue ) );
		self::assertNull( $this->rows->find( 'B0' . sprintf( '%08d', 100 ), 'us' ), 'beyond the batch' );
	}

	public function test_a_run_stops_short_of_the_days_quota_keeping_a_reserve_for_people(): void {
		$service = $this->service();
		for ( $i = 0; $i < 130; $i++ ) {
			$this->due[] = array( 'asin' => sprintf( 'B0%08d', $i ), 'country' => 'us', 'link' => 'l' );
		}
		// Three calls left before the reserve.
		$this->ledger->log = new RequestLog( gmdate( 'Y-m-d', (int) $this->now ), GuardedCatalog::DAILY_LIMIT - GuardedCatalog::RESERVE - 3, 0.0, 0 );

		$service->tick();

		self::assertSame( array( 3 ), array_map( 'count', $this->amazon->batches ), 'one request for the three rows the quota leaves' );
		self::assertNull( $this->run );
	}

	public function test_the_job_asks_amazon_for_ten_rows_at_a_time(): void {
		$service = $this->service();
		for ( $i = 0; $i < 25; $i++ ) {
			$asin = sprintf( 'B0%08d', $i );
			$this->item( $asin );
			$this->due[] = array( 'asin' => $asin, 'country' => 'us', 'link' => 'l' );
		}
		$this->now -= 100; // A long budget: the run is not cut short here.

		$service->tick();
		while ( null !== $this->run ) {
			$service->process();
		}

		self::assertSame( array( 10, 10, 5 ), array_map( 'count', $this->amazon->batches ) );
		self::assertSame( 0, $this->amazon->asked, 'no single requests' );
		self::assertSame( '$32.97', $this->rows->find( 'B000000024', 'us' )?->price );
	}
}
