<?php
namespace Affibloom\Tests\Unit\Application;

use Affibloom\Application\Products\BackgroundJob;
use Affibloom\Application\Products\BulkAddRunning;
use Affibloom\Application\Products\BulkAddService;
use Affibloom\Application\Products\LinkNormaliser;
use Affibloom\Application\Products\LinkResolver;
use Affibloom\Application\Products\ProductInput;
use Affibloom\Application\Products\ProductService;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\BulkRun;
use Affibloom\Domain\Product\BulkRunRepository;
use Affibloom\Domain\Product\Product;
use Affibloom\Tests\Unit\Fakes\FakePageFetcher;
use Affibloom\Tests\Unit\Fakes\InMemoryProductRepository;
use Affibloom\Tests\Unit\Fakes\InMemorySettingsRepository;
use PHPUnit\Framework\TestCase;

final class BulkAddServiceTest extends TestCase {
	private const NOW = 1_800_000_000.0;

	private InMemoryProductRepository $products;
	/** @var BulkRunRepository&object{run: ?BulkRun} */
	private $runs;
	/** @var BackgroundJob&object{dispatched: int} */
	private $job;
	private float $now = self::NOW;

	protected function setUp(): void {
		parent::setUp();
		$this->products = new InMemoryProductRepository();
		$this->runs     = new class() implements BulkRunRepository {
			public ?BulkRun $run = null;
			public function get(): ?BulkRun {
				return $this->run;
			}
			public function save( BulkRun $run ): void {
				$this->run = $run;
			}
		};
		$this->job = new class() implements BackgroundJob {
			public int $dispatched = 0;
			public function dispatch(): void {
				++$this->dispatched;
			}
		};
	}

	/**
	 * Adding one link, with no network: every page fetch has no answer.
	 */
	private function products(): ProductService {
		$resolver = new LinkResolver( new FakePageFetcher() );
		$settings = new InMemorySettingsRepository();
		$links    = new LinkNormaliser( $resolver, $settings );
		return new ProductService( $this->products, $resolver, $settings, new ProductInput( $links ), $links );
	}

	private function service(): BulkAddService {
		return new BulkAddService( $this->products(), $this->runs, $this->job, fn(): float => $this->now );
	}

	public function test_starting_stores_the_run_and_wakes_the_background_job(): void {
		$total = $this->service()->start( array( 'https://a.example/1', '', 'https://a.example/2' ) );

		self::assertSame( 2, $total );
		self::assertSame( 2, $this->runs->run->total );
		self::assertSame( 1, $this->job->dispatched );
		self::assertCount( 0, $this->products->saved ); // nothing is created before the job runs
	}

	public function test_nothing_to_add_is_refused_like_an_empty_box(): void {
		try {
			$this->service()->start( array( '', '  ' ) );
			self::fail( 'Expected InvalidField.' );
		} catch ( InvalidField $error ) {
			self::assertSame( 'urls', $error->field );
			self::assertSame( 'empty', $error->reason );
		}
		self::assertNull( $this->runs->run );
	}

	public function test_a_second_run_is_refused_while_the_first_is_going(): void {
		$this->service()->start( array( 'https://a.example/1' ) );

		$this->expectException( BulkAddRunning::class );
		$this->service()->start( array( 'https://a.example/2' ) );
	}

	public function test_a_run_older_than_six_hours_no_longer_blocks_a_new_one(): void {
		$this->service()->start( array( 'https://a.example/1' ) );
		$this->now += 6 * 3600;

		self::assertSame( 1, $this->service()->start( array( 'https://a.example/2' ) ) );
	}

	public function test_processing_adds_each_link_and_skips_invalid_lines_and_duplicates_silently(): void {
		$this->service()->start( array( 'https://a.example/1', 'not a link', 'A.EXAMPLE/1/', 'b.example/2' ) );

		$this->service()->process();

		self::assertSame(
			array( 'https://a.example/1', 'https://b.example/2' ),
			array_map( static fn( Product $p ): string => $p->url->value(), $this->products->saved )
		);
		self::assertSame( 4, $this->runs->run->done );
		self::assertSame( array( 'running' => false, 'percent' => 100 ), $this->service()->progress() );
	}

	public function test_a_bare_asin_line_is_added_as_an_amazon_product(): void {
		$this->service()->start( array( 'B0C1234567' ) );

		$this->service()->process();

		self::assertCount( 1, $this->products->saved );
		self::assertSame( 'B0C1234567', $this->products->saved[0]->asin );
	}

	public function test_processing_stops_at_the_time_budget_and_wakes_the_job_again(): void {
		$this->service()->start( array( 'https://a.example/1', 'https://a.example/2', 'https://a.example/3' ) );
		$dispatched = $this->job->dispatched;
		$clock      = array( self::NOW, self::NOW + 5, self::NOW + 21, self::NOW + 22 );
		$service    = new BulkAddService(
			$this->products(),
			$this->runs,
			$this->job,
			static function () use ( &$clock ): float {
				return count( $clock ) > 1 ? array_shift( $clock ) : $clock[0];
			}
		);

		$service->process();

		self::assertSame( 2, $this->runs->run->done );
		self::assertSame( $dispatched + 1, $this->job->dispatched );
	}

	public function test_progress_reports_the_share_done_and_nudges_a_run_that_is_still_going(): void {
		$this->service()->start( array( 'https://a.example/1', 'https://a.example/2' ) );
		$this->runs->run = $this->runs->run->advanced();
		$dispatched      = $this->job->dispatched;

		self::assertSame( array( 'running' => true, 'percent' => 50 ), $this->service()->progress() );
		self::assertSame( $dispatched + 1, $this->job->dispatched );
	}

	public function test_progress_without_a_run_is_finished(): void {
		self::assertSame( array( 'running' => false, 'percent' => 100 ), $this->service()->progress() );
		self::assertSame( 0, $this->job->dispatched );
	}
}
