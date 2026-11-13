<?php
namespace Affibloom\Tests\Unit\Application;

use Affibloom\Application\Products\FetchedPage;
use Affibloom\Application\Products\LinkNormaliser;
use Affibloom\Application\Products\LinkResolver;
use Affibloom\Application\Products\ProductInput;
use Affibloom\Application\Products\ProductService;
use Affibloom\Application\Products\ValidationFailed;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Tests\Unit\Fakes\FakePageFetcher;
use Affibloom\Tests\Unit\Fakes\InMemoryProductRepository;
use Affibloom\Tests\Unit\Fakes\InMemorySettingsRepository;
use Affibloom\Tests\Unit\TestCase;

/**
 * A new Primary URL on Save Changes (Task 15B; F1 requirements 9, S7, S8, D12, D17): the add flow's link rules, the
 * same Amazon product updated in place, and Lasso's name and image rule when the ASIN changes.
 */
final class ProductServiceLinkTest extends TestCase {
	private InMemoryProductRepository $repo;
	private FakePageFetcher $fetcher;

	protected function setUp(): void {
		parent::setUp();
		\Brain\Monkey\Functions\when( 'sanitize_text_field' )->alias( static fn( $s ) => trim( strip_tags( (string) $s ) ) );
		$this->repo    = new InMemoryProductRepository();
		$this->fetcher = new FakePageFetcher();
	}

	/**
	 * @param array<string, mixed> $settings Settings changed from the defaults.
	 */
	private function service( array $settings = array() ): ProductService {
		$repository = new InMemorySettingsRepository( $settings + array( 'tracking_id' => 'vbvault-20' ) );
		$resolver   = new LinkResolver( $this->fetcher );
		$links      = new LinkNormaliser( $resolver, $repository );

		return new ProductService( $this->repo, $resolver, $repository, new ProductInput( $links ), $links );
	}

	/**
	 * @param array<string, mixed> $fields Fields over a non-Amazon product with an image.
	 */
	private function stored( array $fields = array() ): Product {
		return $this->repo->save(
			Product::draft( 'Mizuno Wave', AffiliateUrl::from_string( 'https://www.example.com/mizuno' ), Settings::from_array( array() ) )
				->with( array( 'image_url' => 'https://www.example.com/mizuno.jpg' ) + $fields )
		);
	}

	private function amazon( string $name, string $asin ): Product {
		return $this->stored(
			array(
				'name'           => $name,
				'url'            => AffiliateUrl::from_string( "https://www.amazon.com/dp/$asin?tag=vbvault-20" ),
				'type'           => ProductType::Amazon,
				'asin'           => $asin,
				'amazon_country' => 'us',
			)
		);
	}

	public function test_an_unchanged_link_is_not_followed_and_keeps_everything(): void {
		$product = $this->stored();

		$saved = $this->service()->update( (int) $product->id, array( 'url' => ' https://www.example.com/mizuno ' ) );

		self::assertSame( 'https://www.example.com/mizuno', $saved->url->value() );
		self::assertSame( 'https://www.example.com/mizuno.jpg', $saved->image_url );
		self::assertSame( array(), $this->fetcher->asked );
	}

	public function test_an_unchanged_amazon_link_gets_the_sites_tag_again(): void {
		$product = $this->amazon( 'Molten V5M5000', 'B0C1234567' );
		$product = $this->repo->save( $product->with( array( 'url' => AffiliateUrl::from_string( 'https://www.amazon.com/dp/B0C1234567?tag=old-20' ) ) ) );

		$saved = $this->service()->update( (int) $product->id, array( 'url' => 'https://www.amazon.com/dp/B0C1234567?tag=old-20' ) );

		self::assertSame( 'https://www.amazon.com/dp/B0C1234567?tag=vbvault-20', $saved->url->value() ); // D12
	}

	public function test_another_amazon_product_is_tagged_and_a_placeholder_name_takes_its_starting_name(): void {
		$product = $this->amazon( 'Amazon', 'B0C1234567' );

		$saved = $this->service()->update(
			(int) $product->id,
			array(
				'name' => 'Amazon',
				'url'  => 'https://www.amazon.com/Mikasa-V200W-Volleyball/dp/B07WPQR123?tag=someone-20&psc=1',
			)
		);

		self::assertSame( 'https://www.amazon.com/Mikasa-V200W-Volleyball/dp/B07WPQR123?tag=vbvault-20', $saved->url->value() );
		self::assertSame( ProductType::Amazon, $saved->type );
		self::assertSame( 'B07WPQR123', $saved->asin );
		self::assertSame( 'us', $saved->amazon_country );
		self::assertSame( 'Mikasa V200W Volleyball', $saved->name ); // decision 11's starting name (D17)
		self::assertSame( '', $saved->image_url ); // the new product's image: none without Amazon data (D17)
	}

	public function test_a_real_name_is_kept_while_keep_title_is_on_but_the_image_follows_the_product(): void {
		$product = $this->amazon( 'Molten V5M5000', 'B0C1234567' );

		$saved = $this->service()->update( (int) $product->id, array( 'url' => 'https://www.amazon.com/Mikasa-V200W/dp/B07WPQR123' ) );

		self::assertSame( 'Molten V5M5000', $saved->name );
		self::assertSame( '', $saved->image_url );
	}

	public function test_with_keep_title_off_a_real_name_is_replaced_too(): void {
		$product = $this->amazon( 'Molten V5M5000', 'B0C1234567' );

		$saved = $this->service( array( 'keep_title_when_url_replaced' => false ) )->update(
			(int) $product->id,
			array( 'url' => 'https://www.amazon.com/Mikasa-V200W/dp/B07WPQR123' )
		);

		self::assertSame( 'Mikasa V200W', $saved->name );
	}

	public function test_a_media_library_image_stays_when_the_asin_changes(): void {
		$product = $this->repo->save( $this->amazon( 'Amazon', 'B0C1234567' )->with( array( 'image_id' => 42 ) ) );

		$saved = $this->service()->update( (int) $product->id, array( 'url' => 'https://www.amazon.com/dp/B07WPQR123' ) );

		self::assertSame( 'https://www.example.com/mizuno.jpg', $saved->image_url ); // Lasso: the attachment wins (S6)
		self::assertSame( 42, $saved->image_id );
	}

	public function test_a_short_link_to_the_same_asin_is_kept_as_typed_with_the_name_and_image(): void {
		$product       = $this->amazon( 'Molten V5M5000', 'B0C1234567' );
		$this->fetcher = new FakePageFetcher( array( 'https://amzn.to/3abcDEF' => new FetchedPage( 200, 'https://www.amazon.com/dp/B0C1234567?tag=other-20', '' ) ) );

		$saved = $this->service()->update( (int) $product->id, array( 'url' => 'https://amzn.to/3abcDEF' ) );

		self::assertSame( 'https://amzn.to/3abcDEF', $saved->url->value() );
		self::assertSame( 'Molten V5M5000', $saved->name );
		self::assertSame( 'https://www.example.com/mizuno.jpg', $saved->image_url );
		self::assertSame( 'B0C1234567', $saved->asin );
	}

	public function test_a_short_link_to_another_asin_is_stored_as_its_tagged_store_link(): void {
		$product       = $this->amazon( 'Amazon', 'B0C1234567' );
		$this->fetcher = new FakePageFetcher( array( 'https://amzn.to/9zyxWVU' => new FetchedPage( 200, 'https://www.amazon.com/Tachikara-Ball/dp/B07WPQR123?tag=other-20', '' ) ) );

		$saved = $this->service()->update( (int) $product->id, array( 'url' => 'https://amzn.to/9zyxWVU' ) );

		self::assertSame( 'https://www.amazon.com/Tachikara-Ball/dp/B07WPQR123?tag=vbvault-20', $saved->url->value() );
		self::assertSame( 'B07WPQR123', $saved->asin );
		self::assertSame( 'Tachikara Ball', $saved->name );
	}

	public function test_an_amazon_product_given_another_shops_link_loses_its_amazon_keys(): void {
		$product = $this->amazon( 'Molten V5M5000', 'B0C1234567' );

		$saved = $this->service()->update( (int) $product->id, array( 'url' => 'www.example.com/molten' ) );

		self::assertSame( 'https://www.example.com/molten', $saved->url->value() );
		self::assertSame( ProductType::Other, $saved->type );
		self::assertSame( '', $saved->asin );
		self::assertSame( '', $saved->amazon_country );
		self::assertSame( 'Molten V5M5000', $saved->name );
		self::assertSame( 'https://www.example.com/mizuno.jpg', $saved->image_url );
	}

	public function test_a_bare_asin_is_the_default_stores_product(): void {
		$product = $this->stored();

		$saved = $this->service()->update( (int) $product->id, array( 'url' => 'b07wpqr123' ) );

		self::assertSame( 'https://www.amazon.com/dp/B07WPQR123?tag=vbvault-20', $saved->url->value() );
		self::assertSame( ProductType::Amazon, $saved->type );
	}

	public function test_an_empty_link_and_an_empty_name_are_refused_together(): void {
		$product = $this->stored();

		try {
			$this->service()->update( (int) $product->id, array( 'name' => '', 'url' => '  ' ) );
			self::fail( 'An empty name and link must be refused.' );
		} catch ( ValidationFailed $refused ) {
			self::assertSame( array( 'name' => 'required', 'url' => 'required' ), $refused->errors );
		}
		self::assertSame( 'https://www.example.com/mizuno', $this->repo->get( (int) $product->id )?->url->value() );
	}

	public function test_something_that_is_not_a_link_is_refused_as_on_adding(): void {
		$product = $this->stored();

		try {
			$this->service()->update( (int) $product->id, array( 'url' => 'not a link' ) );
			self::fail( 'A non-link must be refused.' );
		} catch ( ValidationFailed $refused ) {
			self::assertSame( array( 'url' => 'invalid_url' ), $refused->errors );
		}
	}
}
