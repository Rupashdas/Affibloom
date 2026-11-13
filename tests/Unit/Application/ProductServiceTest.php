<?php
namespace Affibloom\Tests\Unit\Application;

use Affibloom\Application\Products\FetchedPage;
use Affibloom\Application\Products\LinkNormaliser;
use Affibloom\Application\Products\LinkResolver;
use Affibloom\Application\Products\ProductInput;
use Affibloom\Application\Products\ProductService;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Domain\Settings\Settings;
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
		$resolver   = new LinkResolver( $this->fetcher );
		$repository = new InMemorySettingsRepository( $settings );
		$links      = new LinkNormaliser( $resolver, $repository );
		return new ProductService( $this->repo, $resolver, $repository, new ProductInput( $links ), $links );
	}

	/**
	 * Lasso re-saves the product it finds (wp_update_post after is_lasso_url_exist), which makes it the most
	 * recently changed: it moves to the top of the Dashboard, the only sign an Amazon re-add gives (url-add.php
	 * reloads the Dashboard with no message).
	 */
	public function test_adding_a_link_the_site_has_moves_that_product_to_the_top_of_the_list(): void {
		$service = $this->service();
		$first   = $service->create_from_url( 'https://www.example.com/first' )->product;
		$service->create_from_url( 'https://www.example.com/second' );

		$again = $service->create_from_url( 'https://www.example.com/first' );

		self::assertTrue( $again->already_existed );
		self::assertSame( $first->id, $again->product->id );
		$top = $service->list( \Affibloom\Domain\Product\SearchTerm::from_input( '' ), \Affibloom\Domain\Product\LinkKind::All, 1, 10 )->items[0];
		self::assertSame( $first->id, $top->id );
	}

	public function test_a_product_is_found_by_its_id_for_its_page(): void {
		$service = $this->service();
		$added   = $service->create_from_url( 'https://www.example.com/page' )->product;

		self::assertSame( 'https://www.example.com/page', $service->get( (int) $added->id )?->url->value() );
		self::assertNull( $service->get( 999 ) );
		self::assertNull( $service->get( 0 ) );
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
		self::assertSame( 'Mizuno Wave', $added->product->name ); // the slug, decision 11 (Task 12E)
		self::assertSame( array(), $this->fetcher->asked );
	}

	public function test_the_same_asin_in_another_form_is_the_product_that_exists_without_a_request(): void {
		$service = $this->service( self::LIVE_AMAZON );
		$first   = $service->create_from_url( 'https://www.amazon.com/dp/B0C1234567' );

		foreach ( array( 'https://www.amazon.com/gp/product/B0C1234567?th=1', 'https://www.amazon.com/Mizuno-Wave/dp/b0c1234567/ref=x', 'https://amazon.com/gp/aw/d/B0C1234567' ) as $form ) {
			$added = $service->create_from_url( $form );

			self::assertTrue( $added->already_existed, $form );
			self::assertSame( $first->product->id, $added->product->id, $form );
		}
		self::assertCount( 1, $this->repo->saved );
		self::assertSame( array(), $this->fetcher->asked );
	}

	public function test_the_same_asin_on_another_store_is_a_new_product(): void {
		$service = $this->service( self::LIVE_AMAZON );
		$service->create_from_url( 'https://www.amazon.com/dp/B0C1234567' );

		$added = $service->create_from_url( 'https://www.amazon.co.uk/dp/B0C1234567' );

		self::assertFalse( $added->already_existed );
		self::assertSame( 'gb', $added->product->amazon_country );
		self::assertCount( 2, $this->repo->saved );
	}

	public function test_a_non_amazon_link_is_never_matched_by_asin(): void {
		$service = $this->service( self::LIVE_AMAZON );
		$service->create_from_url( 'https://www.amazon.com/dp/B0C1234567' );

		self::assertFalse( $service->create_from_url( 'https://www.example.com/dp/B0C1234567' )->already_existed );
	}

	public function test_a_search_page_is_stored_whole_typed_amazon_with_its_search_key(): void {
		$added = $this->service( self::LIVE_AMAZON )->create_from_url( 'https://www.amazon.com/s?k=nike+kd14&crid=X1&tag=someone-20' );

		self::assertSame( 'https://www.amazon.com/s?k=nike%20kd14&crid=X1&tag=vbvault-20', $added->product->url->value() );
		self::assertSame( ProductType::Amazon, $added->product->type );
		self::assertSame( '', $added->product->asin );
		self::assertSame( 'amazon.com|nike kd14', $added->product->search_key );
		self::assertSame( array(), $this->fetcher->asked );
	}

	public function test_the_same_search_written_otherwise_is_the_product_that_exists(): void {
		$service = $this->service( self::LIVE_AMAZON );
		$first   = $service->create_from_url( 'https://www.amazon.com/s?k=nike+kd14&crid=X1' );

		$added = $service->create_from_url( 'https://www.amazon.com/s/ref=nb_sb_noss?k=NIKE%20%20KD14' );

		self::assertTrue( $added->already_existed );
		self::assertSame( $first->product->id, $added->product->id );
		self::assertFalse( $service->create_from_url( 'https://www.amazon.co.uk/s?k=nike+kd14' )->already_existed );
	}

	public function test_another_amazon_page_is_found_again_by_its_tagged_link(): void {
		$service = $this->service( self::LIVE_AMAZON );
		$first   = $service->create_from_url( 'https://www.amazon.com/stores/Mikasa/page/1?ref_=z&tag=someone-20' );
		self::assertSame( 'https://www.amazon.com/stores/Mikasa/page/1?ref_=z&tag=vbvault-20', $first->product->url->value() );

		$added = $service->create_from_url( 'https://www.amazon.com/stores/Mikasa/page/1?ref_=z&tag=another-21' );

		self::assertTrue( $added->already_existed );
		self::assertSame( $first->product->id, $added->product->id );
	}

	public function test_a_matched_amazon_product_lasso_named_sorry_is_renamed(): void {
		$this->repo->save(
			Product::draft( 'Sorry! Something went wrong!', AffiliateUrl::from_string( 'https://www.amazon.com/dp/B0C1234567?tag=vbvault-20' ), Settings::from_array( array() ) )
				->with( array( 'type' => ProductType::Amazon, 'asin' => 'B0C1234567', 'amazon_country' => 'us' ) )
		);

		$added = $this->service( self::LIVE_AMAZON )->create_from_url( 'https://www.amazon.com/gp/product/B0C1234567' );

		self::assertTrue( $added->already_existed );
		self::assertSame( 'Amazon', $added->product->name ); // get_search_page_title() of a product link
		self::assertSame( 'Amazon', $this->repo->saved[0]->name );
	}

	public function test_a_non_amazon_product_named_wrong_is_left_alone(): void {
		$this->repo->save( Product::draft( 'Page went wrong!', AffiliateUrl::from_string( 'https://www.example.com/x' ), Settings::from_array( array() ) ) );

		self::assertSame( 'Page went wrong!', $this->service()->create_from_url( 'https://www.example.com/x' )->product->name );
	}

	public function test_a_short_link_is_stored_as_the_product_link_it_opens(): void {
		$this->fetcher = new FakePageFetcher( array( 'https://amzn.to/46fBGAJ' => new FetchedPage( 301, 'https://www.amazon.com/Stabilizer/dp/B00XOUQ8PE?tag=someone-20&ref_=x', '' ) ) );

		$added = $this->service( self::LIVE_AMAZON )->create_from_url( 'https://amzn.to/46fBGAJ' );

		self::assertSame( 'https://www.amazon.com/Stabilizer/dp/B00XOUQ8PE?tag=vbvault-20&ref_=x', $added->product->url->value() );
		self::assertSame( ProductType::Amazon, $added->product->type );
		self::assertSame( 'B00XOUQ8PE', $added->product->asin );
		self::assertSame( 'us', $added->product->amazon_country );
	}

	public function test_a_short_link_to_a_product_that_exists_opens_it(): void {
		$service = $this->service( self::LIVE_AMAZON );
		$first   = $service->create_from_url( 'https://www.amazon.com/dp/B00XOUQ8PE' );
		$this->fetcher = new FakePageFetcher( array( 'https://amzn.to/46fBGAJ' => new FetchedPage( 301, 'https://www.amazon.com/Stabilizer/dp/B00XOUQ8PE?th=1', '' ) ) );

		$added = $this->service( self::LIVE_AMAZON )->create_from_url( 'https://amzn.to/46fBGAJ' );

		self::assertTrue( $added->already_existed );
		self::assertSame( $first->product->id, $added->product->id );
		self::assertCount( 1, $this->repo->saved );
	}

	public function test_a_short_link_whose_destination_is_stored_is_that_product(): void {
		$service = $this->service( self::LIVE_AMAZON );
		$first   = $service->create_from_url( 'https://www.amazon.com/s?k=nike+kd14&tag=vbvault-20' );
		$this->fetcher = new FakePageFetcher( array( 'https://amzn.to/3NGJEvm' => new FetchedPage( 301, 'https://www.amazon.com/s?k=nike%20kd14&tag=vbvault-20', '' ) ) );

		self::assertSame( $first->product->id, $this->service( self::LIVE_AMAZON )->create_from_url( 'https://amzn.to/3NGJEvm' )->product->id );
	}

	public function test_a_short_link_that_cannot_be_followed_is_kept_typed_amazon(): void {
		$added = $this->service( self::LIVE_AMAZON )->create_from_url( 'https://amzn.to/46fBGAJ' );

		self::assertSame( 'https://amzn.to/46fBGAJ', $added->product->url->value() );
		self::assertSame( ProductType::Amazon, $added->product->type );
		self::assertSame( '', $added->product->asin );
		self::assertSame( 'Amazon', $added->product->name );
		self::assertSame( array( 'https://amzn.to/46fBGAJ' ), $this->fetcher->asked );
	}

	public function test_a_bare_asin_becomes_an_amazon_product(): void {
		$added = $this->service( self::LIVE_AMAZON )->create_from_url( ' b0c1234567 ' );

		self::assertSame( 'https://www.amazon.com/dp/B0C1234567?tag=vbvault-20', $added->product->url->value() );
		self::assertSame( ProductType::Amazon, $added->product->type );
		self::assertSame( 'B0C1234567', $added->product->asin );
		self::assertSame( 'Amazon', $added->product->name );
	}

	public function test_a_bare_asin_of_a_product_that_exists_opens_it(): void {
		$service = $this->service( self::LIVE_AMAZON );
		$first   = $service->create_from_url( 'https://www.amazon.com/Mizuno/dp/B0C1234567?tag=x' );

		self::assertSame( $first->product->id, $service->create_from_url( 'B0C1234567' )->product->id );
	}

	public function test_a_new_amazon_product_is_named_from_its_slug(): void {
		$added = $this->service( self::LIVE_AMAZON )->create_from_url( 'https://www.amazon.com/Mizuno-Wave-Momentum-3/dp/B0C1234567' );

		self::assertSame( 'Mizuno Wave Momentum 3', $added->product->name );
	}

	public function test_a_new_search_page_is_named_after_its_keywords(): void {
		self::assertSame( 'Amazon: nike kd14', $this->service( self::LIVE_AMAZON )->create_from_url( 'https://www.amazon.com/s?k=nike+kd14' )->product->name );
	}

	public function test_a_followed_short_link_is_named_from_its_destination_s_slug(): void {
		$this->fetcher = new FakePageFetcher( array( 'https://amzn.to/46fBGAJ' => new FetchedPage( 301, 'https://www.amazon.com/Active-Ankle-329/dp/B00XOUQ8PE', '' ) ) );

		self::assertSame( 'Active Ankle 329', $this->service( self::LIVE_AMAZON )->create_from_url( 'https://amzn.to/46fBGAJ' )->product->name );
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
