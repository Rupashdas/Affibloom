<?php
namespace Affibloom\Tests\Unit\Application;

use Affibloom\Application\Products\DuplicateLink;
use Affibloom\Application\Products\LinkNormaliser;
use Affibloom\Application\Products\LinkResolver;
use Affibloom\Application\Products\ProductInput;
use Affibloom\Application\Products\ProductService;
use Affibloom\Application\Products\RepointRefused;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Tests\Unit\Fakes\FakePageFetcher;
use Affibloom\Tests\Unit\Fakes\InMemoryProductRepository;
use Affibloom\Tests\Unit\Fakes\InMemorySettingsRepository;
use Affibloom\Tests\Unit\TestCase;

/**
 * "This Product Already Exists" (Task 15C; F1 requirements 9, S10, D14): a Primary URL another product already has
 * is not saved, and the repoint retires this product in favour of that one, without touching a post.
 */
final class ProductServiceRepointTest extends TestCase {
	private InMemoryProductRepository $repo;

	protected function setUp(): void {
		parent::setUp();
		\Brain\Monkey\Functions\when( 'sanitize_text_field' )->alias( static fn( $s ) => trim( strip_tags( (string) $s ) ) );
		$this->repo = new InMemoryProductRepository();
	}

	private function service(): ProductService {
		$settings = new InMemorySettingsRepository( array( 'tracking_id' => 'vbvault-20' ) );
		$resolver = new LinkResolver( new FakePageFetcher() );
		$links    = new LinkNormaliser( $resolver, $settings );

		return new ProductService( $this->repo, $resolver, $settings, new ProductInput( $links ), $links );
	}

	private function product( string $name, string $url, string $asin = '' ): Product {
		$fields = '' === $asin ? array() : array(
			'type'           => ProductType::Amazon,
			'asin'           => $asin,
			'amazon_country' => 'us',
		);

		return $this->repo->save( Product::draft( $name, AffiliateUrl::from_string( $url ), Settings::from_array( array() ) )->with( $fields ) );
	}

	public function test_a_link_another_product_has_is_not_saved_and_names_that_product(): void {
		$mine  = $this->product( 'Mine', 'https://www.example.com/mine' );
		$other = $this->product( 'Other', 'https://www.example.com/other' );

		try {
			$this->service()->update( (int) $mine->id, array( 'name' => 'Renamed', 'url' => 'www.example.com/other/' ) );
			self::fail( 'The duplicate must be offered, not saved.' );
		} catch ( DuplicateLink $duplicate ) {
			self::assertSame( $other->id, $duplicate->other->id );
		}
		self::assertSame( 'Mine', $this->repo->get( (int) $mine->id )?->name );
		self::assertSame( 'https://www.example.com/mine', $this->repo->get( (int) $mine->id )?->url->value() );
	}

	public function test_an_amazon_link_to_another_products_asin_is_a_duplicate_whatever_its_tag(): void {
		$mine  = $this->product( 'Mine', 'https://www.amazon.com/dp/B0C1234567?tag=vbvault-20', 'B0C1234567' );
		$other = $this->product( 'Other', 'https://www.amazon.com/dp/B07WPQR123?tag=vbvault-20', 'B07WPQR123' );

		$this->expectExceptionObject( new DuplicateLink( $other ) );
		$this->service()->update( (int) $mine->id, array( 'url' => 'https://www.amazon.com/Some-Name/dp/B07WPQR123?tag=someone-20' ) );
	}

	public function test_a_product_is_not_a_duplicate_of_itself(): void {
		$mine = $this->product( 'Mine', 'https://www.amazon.com/dp/B0C1234567?tag=vbvault-20', 'B0C1234567' );

		$saved = $this->service()->update( (int) $mine->id, array( 'url' => 'https://www.amazon.com/Other-Words/dp/B0C1234567' ) );

		self::assertSame( $mine->id, $saved->id );
	}

	public function test_confirming_retires_this_product_in_favour_of_the_other(): void {
		$mine  = $this->product( 'Mine', 'https://www.example.com/mine' );
		$other = $this->product( 'Other', 'https://www.example.com/other' );

		$kept = $this->service()->repoint( (int) $mine->id, (int) $other->id, 'www.example.com/other' );

		self::assertSame( $other->id, $kept->id );
		self::assertSame( array( (int) $mine->id => (int) $other->id ), $this->repo->retired );
		self::assertNull( $this->repo->get( (int) $mine->id ) ); // in the trash: no longer published
		self::assertNotNull( $this->repo->get( (int) $other->id ) );
	}

	/**
	 * Lasso_Repoint_Merge::merge()'s checks, in its order.
	 *
	 * @return array<string, array{0: string, 1: string, 2: string}>
	 */
	public static function refusals(): array {
		return array(
			'the same product twice'       => array( 'mine', 'mine', 'repoint_ids' ),
			'this product no longer there' => array( 'gone', 'other', 'repoint_source' ),
			'the other no longer there'    => array( 'mine', 'gone', 'repoint_target' ),
		);
	}

	/**
	 * @dataProvider refusals
	 */
	public function test_a_repoint_that_no_longer_fits_is_refused( string $from, string $to, string $reason ): void {
		$ids = array(
			'mine'  => (int) $this->product( 'Mine', 'https://www.example.com/mine' )->id,
			'other' => (int) $this->product( 'Other', 'https://www.example.com/other' )->id,
			'gone'  => 999,
		);

		try {
			$this->service()->repoint( $ids[ $from ], $ids[ $to ], 'https://www.example.com/other' );
			self::fail( 'The repoint must be refused.' );
		} catch ( RepointRefused $refused ) {
			self::assertSame( $reason, $refused->reason );
		}
		self::assertSame( array(), $this->repo->retired );
	}

	public function test_a_link_that_no_longer_leads_to_the_other_product_is_refused(): void {
		$mine  = $this->product( 'Mine', 'https://www.example.com/mine' );
		$other = $this->product( 'Other', 'https://www.example.com/other' );
		$this->product( 'Third', 'https://www.example.com/third' );

		foreach ( array( 'https://www.example.com/third', 'https://www.example.com/nobody', '' ) as $link ) {
			try {
				$this->service()->repoint( (int) $mine->id, (int) $other->id, $link );
				self::fail( "The repoint to $link must be refused." );
			} catch ( RepointRefused $refused ) {
				self::assertSame( '' === $link ? 'repoint_url' : 'repoint_mismatch', $refused->reason );
			}
		}
		self::assertSame( array(), $this->repo->retired );
	}
}
