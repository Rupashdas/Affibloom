<?php
namespace Affibloom\Tests\Unit\Application;

use Affibloom\Application\Amazon\AmazonDataService;
use Affibloom\Application\Amazon\BannerDismissals;
use Affibloom\Application\Amazon\BulkSyncRefused;
use Affibloom\Application\Amazon\BulkSyncService;
use Affibloom\Application\Products\BackgroundJob;
use Affibloom\Application\Products\FetchedPage;
use Affibloom\Application\Products\LinkNormaliser;
use Affibloom\Application\Products\LinkResolver;
use Affibloom\Domain\Amazon\AmazonProduct;
use Affibloom\Domain\Amazon\BulkSyncRun;
use Affibloom\Domain\Amazon\BulkSyncRunRepository;
use Affibloom\Domain\Amazon\CatalogItem;
use Affibloom\Domain\Amazon\Credentials;
use Affibloom\Domain\Amazon\PendingAmazonSync;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Tests\Unit\Domain\Amazon\CatalogItemTest;
use Affibloom\Tests\Unit\Fakes\FakeAmazonCatalog;
use Affibloom\Tests\Unit\Fakes\FakePageFetcher;
use Affibloom\Tests\Unit\Fakes\InMemoryAmazonProducts;
use Affibloom\Tests\Unit\Fakes\InMemoryCredentials;
use Affibloom\Tests\Unit\Fakes\InMemoryProductRepository;
use Affibloom\Tests\Unit\Fakes\InMemorySettingsRepository;
use PHPUnit\Framework\TestCase;

/**
 * Bulk Amazon sync (C1I; R40-R44, D12; AC14): the banner, starting a run, the background passes and one product.
 */
final class BulkSyncServiceTest extends TestCase {
	private FakeAmazonCatalog $amazon;
	private InMemoryAmazonProducts $rows;
	private InMemoryProductRepository $library;
	private FakePageFetcher $fetcher;
	/** @var PendingAmazonSync&object{ids: list<int>} */
	private $pending;
	/** @var BulkSyncRunRepository&object{run: ?BulkSyncRun} */
	private $runs;
	/** @var BannerDismissals&object{dismissed: array<int, string>} */
	private $dismissals;
	/** @var BackgroundJob&object{dispatched: int} */
	private $job;
	private float $now = 1_700_000_000.0;
	/** @var list<int> */
	private array $paused = array();

	/** @param array<string, ?FetchedPage> $pages What each link answers (FakePageFetcher). */
	private function service( bool $configured = true, array $pages = array() ): BulkSyncService {
		$this->amazon  = new FakeAmazonCatalog();
		$this->rows    = new InMemoryAmazonProducts();
		$this->library = new InMemoryProductRepository();
		$this->fetcher = new FakePageFetcher( $pages );
		$this->pending = new class() implements PendingAmazonSync {
			/** @var list<int> */
			public array $ids = array();
			public function ids( int $limit = 0 ): array {
				return $limit > 0 ? array_slice( $this->ids, 0, $limit ) : $this->ids;
			}
		};
		$this->runs = new class() implements BulkSyncRunRepository {
			public ?BulkSyncRun $run = null;
			public function get(): ?BulkSyncRun {
				return null === $this->run ? null : BulkSyncRun::from_array( $this->run->to_array() );
			}
			public function save( BulkSyncRun $run ): void {
				$this->run = $run;
			}
		};
		$this->dismissals = new class() implements BannerDismissals {
			/** @var array<int, string> */
			public array $dismissed = array();
			public function get( int $user ): string {
				return $this->dismissed[ $user ] ?? '';
			}
			public function set( int $user, string $fingerprint ): void {
				$this->dismissed[ $user ] = $fingerprint;
			}
		};
		$this->job = new class() implements BackgroundJob {
			public int $dispatched = 0;
			public int $delay      = 0;
			public function dispatch( int $delay = 0 ): void {
				++$this->dispatched;
				$this->delay = $delay;
			}
		};

		$credentials = $configured ? new Credentials( 'id', 'secret', 'tag-20', '3.1' ) : new Credentials();
		$data        = new AmazonDataService( new InMemoryCredentials( $credentials ), $this->amazon, $this->rows, $this->library );
		$settings    = new InMemorySettingsRepository( array( 'tracking_id' => 'vbvault-20' ) );

		return new BulkSyncService(
			$this->pending,
			$this->runs,
			$this->dismissals,
			$this->job,
			$data,
			$this->library,
			new LinkNormaliser( new LinkResolver( $this->fetcher ), $settings ),
			$settings,
			fn(): float => $this->now,
			function ( int $seconds ): void {
				$this->paused[] = $seconds;
				$this->now     += $seconds;
			}
		);
	}

	private function amazon_product( string $name, string $asin, string $image = '' ): int {
		$product = Product::draft( $name, AffiliateUrl::from_string( "https://www.amazon.com/dp/$asin?tag=vbvault-20" ), Settings::from_array( array() ) )->with(
			array(
				'type'           => ProductType::Amazon,
				'asin'           => $asin,
				'amazon_country' => 'us',
				'image_url'      => $image,
			)
		);
		$id                    = (int) $this->library->save( $product )->id;
		$this->pending->ids[] = $id;
		return $id;
	}

	private function item( string $asin ): void {
		$item         = CatalogItemTest::recorded( 'getItems-ok.json' );
		$item['asin'] = $asin;
		$this->amazon->items[ $asin ] = CatalogItem::from_creators( $item );
	}

	public function test_the_banner_shows_the_pending_count_until_this_person_dismisses_that_set(): void {
		$service = $this->service( false );
		self::assertNull( $service->banner( 7 ), 'nothing pending: no banner' );

		$this->amazon_product( 'Amazon', 'B0AAAAAAA1' );
		$this->amazon_product( 'Amazon', 'B0AAAAAAA2' );
		self::assertSame( array( 'pending' => 2, 'running' => false, 'can_sync' => false ), $service->banner( 7 ) );

		$service->dismiss( 7 );
		self::assertNull( $service->banner( 7 ) );
		self::assertNotNull( $service->banner( 8 ), 'per person' );

		$this->amazon_product( 'Amazon', 'B0AAAAAAA3' );
		self::assertSame( 3, $service->banner( 7 )['pending'] ?? null, 'a new pending set shows it again' );
	}

	public function test_a_run_is_refused_without_credentials_with_nothing_pending_and_while_one_runs(): void {
		$service = $this->service( false );
		$this->amazon_product( 'Amazon', 'B0AAAAAAA1' );
		self::assertSame( BulkSyncRefused::NOT_CONFIGURED, self::refusal( $service ) );

		$service = $this->service();
		self::assertSame( BulkSyncRefused::NOTHING, self::refusal( $service ) );

		$this->amazon_product( 'Amazon', 'B0AAAAAAA1' );
		$status = $service->start();
		self::assertTrue( $status['running'] );
		self::assertGreaterThan( 0, $this->job->dispatched, 'the job is woken' );
		self::assertSame( BulkSyncRefused::RUNNING, self::refusal( $service ) );
	}

	private static function refusal( BulkSyncService $service ): string {
		try {
			$service->start();
		} catch ( BulkSyncRefused $refused ) {
			return $refused->reason;
		}
		return 'started';
	}

	public function test_a_run_syncs_each_product_and_ends_with_its_summary(): void {
		$service = $this->service();
		$found   = $this->amazon_product( 'Amazon', 'B0FOUND001' );
		$own     = $this->amazon_product( 'My own name', 'B0FOUND002', 'https://example.com/own.jpg' );
		$missing = $this->amazon_product( 'Gone', 'B0MISSING1' );
		$this->item( 'B0FOUND001' );
		$this->item( 'B0FOUND002' );

		$service->start();
		$service->process();

		$status = $service->status();
		self::assertFalse( $status['running'] );
		self::assertTrue( $status['completed'] );
		self::assertSame( 100, $status['percent'] );
		self::assertSame( array( 'succeeded' => 2, 'skipped' => 0, 'failed' => 1 ), $status['counts'] );
		self::assertSame( array( array( 'id' => $missing, 'reason' => 'not_found' ) ), $status['failures'] );
		self::assertSame( array( 1, 1 ), $this->paused, 'a second between products, none after the last' );

		// A placeholder name takes Amazon's title and an empty image its link; an editor's own name and image stay.
		self::assertSame( 'Mikasa V200W Official Game Volleyball', $this->library->get( $found )?->name );
		self::assertSame( 'https://m.media-amazon.com/images/I/71stubLarge.jpg', $this->library->get( $found )?->image_url );
		self::assertSame( 'My own name', $this->library->get( $own )?->name );
		self::assertSame( 'https://example.com/own.jpg', $this->library->get( $own )?->image_url );
		self::assertNotNull( $this->rows->find( 'B0FOUND002', 'us' ) );
	}

	public function test_a_pass_stops_at_its_time_budget_and_wakes_the_job_again(): void {
		$service = $this->service();
		for ( $i = 1; $i <= 30; $i++ ) {
			$this->amazon_product( 'Amazon', sprintf( 'B0BUDGET%02d', $i ) );
		}
		$service->start();
		$dispatched = $this->job->dispatched;

		$service->process();

		self::assertSame( 20, 30 - count( $this->runs->run?->pending ?? array() ), 'twenty products at a second each' );
		self::assertSame( $dispatched + 1, $this->job->dispatched );
		self::assertTrue( $service->status()['running'] );
	}

	public function test_stored_data_is_used_without_asking_amazon(): void {
		$service = $this->service();
		$id      = $this->amazon_product( 'Amazon', 'B0STORED01' );
		$this->rows->save( new AmazonProduct( 'B0STORED01', 'us', 'Stored name', '', 'USD', 'b', 'm', 'https://m.media-amazon.com/s.jpg', false, '', 0, '', array(), 'lasso', '2026-11-09 00:11:00' ) );

		$outcome = $service->sync( $id );

		self::assertSame( array( 'succeeded', 'present' ), array( $outcome->kind, $outcome->reason ) );
		self::assertSame( 'Stored name', $this->library->get( $id )?->name );
		self::assertSame( 'https://m.media-amazon.com/s.jpg', $this->library->get( $id )?->image_url );
	}

	public function test_a_missing_product_fails_and_another_shops_product_is_skipped(): void {
		$service = $this->service();
		$other   = (int) $this->library->save( Product::draft( 'Shop', AffiliateUrl::from_string( 'https://www.example.com/x' ), Settings::from_array( array() ) ) )->id;

		self::assertSame( array( 'failed', 'invalid' ), self::pair( $service->sync( 9999 ) ) );
		self::assertSame( array( 'skipped', 'not_amazon' ), self::pair( $service->sync( $other ) ) );
	}

	public function test_an_amazon_short_link_is_followed_to_its_product_then_synced(): void {
		$service = $this->service( true, array( 'https://amzn.to/abc123' => new FetchedPage( 200, 'https://www.amazon.com/dp/B0SHORT001?ref=x', '' ) ) );
		$id      = $this->short_link( 'https://amzn.to/abc123' );
		$this->item( 'B0SHORT001' );

		$outcome = $service->sync( $id );

		self::assertSame( array( 'succeeded', 'synced' ), self::pair( $outcome ) );
		$product = $this->library->get( $id );
		self::assertSame( array( 'B0SHORT001', 'us' ), array( $product?->asin, $product?->amazon_country ) );
		self::assertSame( 'https://www.amazon.com/dp/B0SHORT001?tag=vbvault-20', $product?->url->value() );
		self::assertSame( 'Mikasa V200W Official Game Volleyball', $product?->name );
	}

	public function test_a_short_link_that_does_not_answer_cannot_be_resolved(): void {
		$service = $this->service();
		$id      = $this->short_link( 'https://amzn.to/silent' );

		self::assertSame( array( 'failed', 'unresolved' ), self::pair( $service->sync( $id ) ) );
		self::assertSame( 'https://amzn.to/silent', $this->library->get( $id )?->url->value() );
	}

	public function test_a_short_link_to_a_search_page_gives_a_placeholder_its_search_title(): void {
		$service = $this->service( true, array( 'https://amzn.to/search1' => new FetchedPage( 200, 'https://www.amazon.com/s?k=volleyball+shoes', '' ) ) );
		$id      = $this->short_link( 'https://amzn.to/search1' );

		self::assertSame( array( 'succeeded', 'search_title' ), self::pair( $service->sync( $id ) ) );
		self::assertSame( 'Amazon: volleyball shoes', $this->library->get( $id )?->name );
	}

	public function test_a_product_gone_since_the_start_fails_and_the_run_goes_on(): void {
		$service = $this->service();
		$this->pending->ids[] = 4242; // No such product.
		$found                = $this->amazon_product( 'Amazon', 'B0FOUND001' );
		$this->item( 'B0FOUND001' );

		$service->start();
		$service->process();

		self::assertSame( array( 'succeeded' => 1, 'skipped' => 0, 'failed' => 1 ), $service->status()['counts'] );
		self::assertSame( 'Mikasa V200W Official Game Volleyball', $this->library->get( $found )?->name );
	}

	private function short_link( string $url ): int {
		$product = Product::draft( 'Amazon', AffiliateUrl::from_string( $url ), Settings::from_array( array() ) )->with( array( 'type' => ProductType::Amazon ) );
		$id      = (int) $this->library->save( $product )->id;
		$this->pending->ids[] = $id;
		return $id;
	}

	/** @return array{string, string} */
	private static function pair( \Affibloom\Application\Amazon\SyncOutcome $outcome ): array {
		return array( $outcome->kind, $outcome->reason );
	}

	public function test_a_run_asks_amazon_for_ten_products_at_a_time(): void {
		$service = $this->service();
		for ( $i = 0; $i < 12; $i++ ) {
			$asin = sprintf( 'B0%08d', $i );
			$this->amazon_product( 'Amazon', $asin );
			$this->item( $asin );
		}
		$service->start();
		while ( null !== $this->runs->get()?->next() ) {
			$service->process();
		}

		self::assertSame( array( 10, 2 ), array_map( 'count', $this->amazon->batches ) );
		self::assertSame( 0, $this->amazon->asked );
		self::assertSame( 12, $this->runs->get()?->counts['succeeded'] ?? null );
	}

	public function test_a_throttled_amazon_pauses_the_run_for_its_wait_without_failing_products(): void {
		$service = $this->service();
		$this->amazon_product( 'Amazon', 'B0AAAAAAA1' );
		$this->amazon->throttled = 90;
		$service->start();
		$service->process();

		self::assertSame( array( 1, 90 ), array( $this->runs->get()?->counts['failed'] === 0 ? 1 : 0, $this->job->delay ) );
		self::assertSame( 1, count( $this->runs->get()?->pending ?? array() ), 'the product waits' );
	}
}
