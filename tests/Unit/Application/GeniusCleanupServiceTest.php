<?php
namespace Affibloom\Tests\Unit\Application;

use Affibloom\Application\Products\FetchedPage;
use Affibloom\Application\Products\GeniusCleanupSchedule;
use Affibloom\Application\Products\GeniusCleanupService;
use Affibloom\Application\Products\LinkNormaliser;
use Affibloom\Application\Products\LinkResolver;
use Affibloom\Application\Products\ProductInput;
use Affibloom\Application\Products\ProductService;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Button;
use Affibloom\Domain\Product\Buttons;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Tests\Unit\Fakes\FakePageFetcher;
use Affibloom\Tests\Unit\Fakes\InMemoryAmazonProducts;
use Affibloom\Tests\Unit\Fakes\InMemoryProductRepository;
use Affibloom\Tests\Unit\Fakes\InMemorySettingsRepository;
use Affibloom\Tests\Unit\TestCase;

/**
 * The geni.us clean-up job (C1P; R63, D14, AC20): "Keep Geni.us Formatting" turned off queues it, turned on clears it;
 * each run re-saves ten geni.us products, newest first, as their destinations, and queues the next ten.
 */
final class GeniusCleanupServiceTest extends TestCase {
	private InMemoryProductRepository $repo;

	/** @var list<int|null> Each next() call's cursor; null for a cancel(). */
	private array $calls = array();

	protected function setUp(): void {
		parent::setUp();
		\Brain\Monkey\Functions\when( 'sanitize_text_field' )->alias( static fn( $s ) => trim( strip_tags( (string) $s ) ) );
		\Brain\Monkey\Functions\when( 'esc_url_raw' )->returnArg();
		\Brain\Monkey\Functions\when( 'wp_kses' )->returnArg();
		$this->repo = new InMemoryProductRepository();
	}

	/**
	 * @param array<string, FetchedPage> $pages Where each geni.us link leads.
	 */
	private function service( array $pages = array(), bool $keep = false ): GeniusCleanupService {
		$settings = new InMemorySettingsRepository(
			array(
				'keep_genius_formatting' => $keep,
				'tracking_id'            => 'vbvault-20',
			)
		);
		$resolver = new LinkResolver( new FakePageFetcher( $pages ) );
		$links    = new LinkNormaliser( $resolver, $settings );
		$products = new ProductService( $this->repo, $resolver, $settings, new ProductInput( $links ), $links, InMemoryAmazonProducts::without_amazon() );
		$test     = $this;
		$schedule = new class( $test ) implements GeniusCleanupSchedule {
			public function __construct( private GeniusCleanupServiceTest $test ) {}

			public function next( int $before ): void {
				$this->test->record( $before );
			}

			public function cancel(): void {
				$this->test->record( null );
			}
		};

		return new GeniusCleanupService( $this->repo, $products, $schedule );
	}

	/** A schedule call, from the fake. */
	public function record( ?int $before ): void {
		$this->calls[] = $before;
	}

	private function product( string $name, string $url, ?Buttons $buttons = null ): Product {
		return $this->repo->save(
			Product::draft( $name, AffiliateUrl::from_string( $url ), Settings::from_array( array() ) )->with( null === $buttons ? array() : array( 'buttons' => $buttons ) )
		);
	}

	private static function leads_to( string $to ): FetchedPage {
		return new FetchedPage( 200, $to, '' );
	}

	public function test_turning_the_switch_off_queues_the_job_and_on_clears_it(): void {
		$service = $this->service();

		$service->switched( true, false );
		$service->switched( false, true );
		$service->switched( false, false );
		$service->switched( true, true );

		self::assertSame( array( 0, null ), $this->calls, 'only a change acts, as Lasso compares the stored value' );
	}

	public function test_a_run_resaves_geni_us_products_as_their_destinations_and_keeps_the_old_link(): void {
		$second  = new Buttons( array( new Button( 'https://geni.us/two', 'Two' ) ) );
		$genius  = $this->product( 'Ball', 'https://geni.us/ball', $second );
		$other   = $this->product( 'Shoe', 'https://www.example.com/shoe' );
		$service = $this->service(
			array(
				'https://geni.us/ball' => self::leads_to( 'https://www.example.com/ball' ),
				'https://geni.us/two'  => self::leads_to( 'https://www.example.com/two' ),
			)
		);

		$service->run( 0 );

		$saved = $this->repo->get( (int) $genius->id );
		self::assertSame( 'https://www.example.com/ball', $saved?->url->value() );
		self::assertSame( 'https://geni.us/ball', $saved?->old_genius_url );
		self::assertSame( 'https://www.example.com/two', $saved?->buttons->all()[0]->url );
		self::assertSame( 'https://geni.us/two', $saved?->old_genius_url2 );
		self::assertSame( 'Ball', $saved?->name );
		self::assertSame( 'https://www.example.com/shoe', $this->repo->get( (int) $other->id )?->url->value() );
		self::assertSame( array(), $this->calls, 'fewer than ten: the job ends' );
	}

	public function test_ten_a_run_newest_first_and_the_next_run_goes_on_below_the_last(): void {
		$ids = array();
		for ( $i = 1; $i <= 12; $i++ ) {
			$ids[] = (int) $this->product( "P$i", "https://geni.us/p$i" )->id;
		}
		// No answer from geni.us: the link stays as it is, and the cursor still moves on (no endless loop).
		$service = $this->service();

		$service->run( 0 );
		self::assertSame( array( $ids[2] ), $this->calls, 'the twelve newest down to the third' );

		$service->run( $ids[2] );
		self::assertSame( array( $ids[2] ), $this->calls, 'the last two, then the job ends' );
	}

	public function test_a_run_with_the_switch_on_again_does_nothing(): void {
		$product = $this->product( 'Ball', 'https://geni.us/ball' );

		$this->service( array( 'https://geni.us/ball' => self::leads_to( 'https://www.example.com/ball' ) ), true )->run( 0 );

		self::assertSame( 'https://geni.us/ball', $this->repo->get( (int) $product->id )?->url->value() );
	}
}
