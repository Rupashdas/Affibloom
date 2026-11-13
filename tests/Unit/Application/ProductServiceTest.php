<?php
namespace Affibloom\Tests\Unit\Application;

use Affibloom\Application\Products\FetchedPage;
use Affibloom\Application\Products\LinkResolver;
use Affibloom\Application\Products\ProductService;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Tests\Unit\Fakes\FakePageFetcher;
use Affibloom\Tests\Unit\Fakes\InMemoryProductRepository;
use Affibloom\Tests\Unit\Fakes\InMemorySettingsRepository;
use PHPUnit\Framework\TestCase;

final class ProductServiceTest extends TestCase {
	private InMemoryProductRepository $repo;
	private FakePageFetcher $fetcher;

	protected function setUp(): void {
		parent::setUp();
		$this->repo    = new InMemoryProductRepository();
		$this->fetcher = new FakePageFetcher();
	}

	/**
	 * @param array<string, mixed> $settings Settings changed from the defaults.
	 */
	private function service( array $settings = array() ): ProductService {
		return new ProductService( $this->repo, new LinkResolver( $this->fetcher ), new InMemorySettingsRepository( $settings ) );
	}

	public function test_a_link_becomes_one_saved_product_named_as_lasso_names_it(): void {
		$link = 'https://mizuno.pxf.io/c/123/456/789?u=https%3A%2F%2Fmizunousa.com%2Fwave';

		$added = $this->service()->create_from_url( " $link " );

		self::assertFalse( $added->already_existed );
		self::assertSame( 100, $added->product->id );
		self::assertSame( 'Mizunousa', $added->product->name ); // the embedded destination's site (Task 11D)
		self::assertSame( $link, $added->product->url->value() ); // the link itself is kept as pasted
		self::assertSame( ProductType::Other, $added->product->type );
		self::assertSame( '', $added->product->asin );
		self::assertSame( '', $added->product->amazon_country );
		self::assertCount( 1, $this->repo->saved );
	}

	/** The live Amazon settings (tests/fixtures/inventory/settings.json). */
	private const LIVE_AMAZON = array(
		'tracking_id'           => 'vbvault-20',
		'multiple_tracking_ids' => true,
		'tracking_id_whitelist' => array( 'vbvault-20' ),
		'shorten_amazon_links'  => true,
	);

	public function test_an_amazon_product_link_is_stored_as_lasso_stores_it_without_a_request(): void {
		$added = $this->service( self::LIVE_AMAZON )->create_from_url( 'https://www.amazon.co.uk/Mizuno-Wave/dp/B0C1234567/ref=sr_1_3?keywords=shoe&tag=someone-20&ref_=x' );

		self::assertSame( 'https://www.amazon.co.uk/Mizuno-Wave/dp/B0C1234567/ref=sr_1_3?tag=vbvault-20&ref_=x', $added->product->url->value() );
		self::assertSame( ProductType::Amazon, $added->product->type );
		self::assertSame( 'B0C1234567', $added->product->asin );
		self::assertSame( 'gb', $added->product->amazon_country );
		self::assertSame( 'Amazon', $added->product->name );
		self::assertSame( array(), $this->fetcher->asked );
	}

	public function test_shorten_amazon_links_off_keeps_the_whole_query(): void {
		$settings = array( 'shorten_amazon_links' => false ) + self::LIVE_AMAZON;

		$added = $this->service( $settings )->create_from_url( 'https://www.amazon.com/dp/B0C1234567?th=1&tag=someone-20' );

		self::assertSame( 'https://www.amazon.com/dp/B0C1234567?th=1&tag=vbvault-20', $added->product->url->value() );
	}

	public function test_a_whitelisted_tag_is_kept(): void {
		$settings = array( 'tracking_id_whitelist' => array( 'other-21', 'vbvault-20' ) ) + self::LIVE_AMAZON;

		$added = $this->service( $settings )->create_from_url( 'https://www.amazon.com/dp/B0C1234567?tag=other-21' );

		self::assertSame( 'https://www.amazon.com/dp/B0C1234567?tag=other-21', $added->product->url->value() );
	}

	public function test_a_page_title_names_a_plain_link(): void {
		$this->fetcher = new FakePageFetcher( array( 'https://shop.example/pads' => new FetchedPage( 200, 'https://shop.example/pads', '<title>Knee Pads</title>' ) ) );

		self::assertSame( 'Knee Pads', $this->service()->create_from_url( 'https://shop.example/pads' )->product->name );
	}

	public function test_a_new_product_takes_the_stored_link_defaults(): void {
		$added = $this->service( array( 'nofollow' => false, 'sponsored' => true ) )->create_from_url( 'https://www.example.com/d' );

		self::assertFalse( $added->product->nofollow );
		self::assertTrue( $added->product->sponsored );
		self::assertTrue( $added->product->new_tab );
		self::assertTrue( $added->product->show_disclosure );
	}

	public function test_a_link_it_already_has_returns_that_product_without_a_request(): void {
		$service = $this->service();
		$service->create_from_url( 'https://www.example.com/a' );
		$asked = count( $this->fetcher->asked );

		$added = $service->create_from_url( 'WWW.EXAMPLE.COM/A/' );

		self::assertTrue( $added->already_existed );
		self::assertSame( 100, $added->product->id );
		self::assertCount( 1, $this->repo->saved );
		self::assertCount( $asked, $this->fetcher->asked );
	}

	public function test_a_refused_link_saves_nothing(): void {
		try {
			$this->service()->create_from_url( 'not a link' );
			self::fail( 'Expected InvalidField.' );
		} catch ( InvalidField $error ) {
			self::assertSame( 'invalid_url', $error->reason );
		}
		self::assertCount( 0, $this->repo->saved );
	}

	public function test_geni_us_is_kept_as_pasted_while_keep_genius_formatting_is_on(): void {
		$this->fetcher = new FakePageFetcher( array( 'https://geni.us/abc' => new FetchedPage( 302, 'https://www.amazon.com/dp/B0CPH9NPK7?tag=vbvault-20', '' ) ) );

		$added = $this->service()->create_from_url( 'https://geni.us/abc' );

		self::assertSame( 'https://geni.us/abc', $added->product->url->value() );
		self::assertSame( 'Amazon', $added->product->name );
		self::assertSame( ProductType::Other, $added->product->type ); // the stored link is geni.us's
	}

	public function test_geni_us_is_stored_as_its_destination_with_the_setting_off(): void {
		$this->fetcher = new FakePageFetcher( array( 'https://geni.us/abc' => new FetchedPage( 302, 'https://www.amazon.com/dp/B0CPH9NPK7?tag=vbvault-20', '' ) ) );

		$added = $this->service( array( 'keep_genius_formatting' => false ) + self::LIVE_AMAZON )->create_from_url( 'https://geni.us/abc' );

		// The destination is then an Amazon product link like any other (Task 12A).
		self::assertSame( 'https://www.amazon.com/dp/B0CPH9NPK7?tag=vbvault-20', $added->product->url->value() );
		self::assertSame( ProductType::Amazon, $added->product->type );
		self::assertSame( 'B0CPH9NPK7', $added->product->asin );
	}

	public function test_with_the_setting_off_a_geni_us_link_to_a_known_product_is_a_duplicate(): void {
		$service = $this->service( array( 'keep_genius_formatting' => false ) );
		$service->create_from_url( 'https://www.amazon.com/dp/B0CPH9NPK7?tag=vbvault-20' );
		$this->fetcher = new FakePageFetcher( array( 'https://geni.us/abc' => new FetchedPage( 302, 'https://www.amazon.com/dp/B0CPH9NPK7?tag=vbvault-20', '' ) ) );

		$added = $this->service( array( 'keep_genius_formatting' => false ) )->create_from_url( 'https://geni.us/abc' );

		self::assertTrue( $added->already_existed );
		self::assertCount( 1, $this->repo->saved );
	}
}
