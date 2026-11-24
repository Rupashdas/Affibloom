<?php
namespace Affibloom\Tests\Unit\Extension;

use Affibloom\Application\Products\LinkNormaliser;
use Affibloom\Application\Products\LinkResolver;
use Affibloom\Application\Products\ProductInput;
use Affibloom\Application\Products\ProductService;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Extension\ProductAccess;
use Affibloom\Tests\Unit\Fakes\FakePageFetcher;
use Affibloom\Tests\Unit\Fakes\InMemoryAmazonProducts;
use Affibloom\Tests\Unit\Fakes\InMemoryProductRepository;
use Affibloom\Tests\Unit\Fakes\InMemorySettingsRepository;
use Affibloom\Tests\Unit\TestCase;
use Brain\Monkey\Functions;
use InvalidArgumentException;

/**
 * Tasks 26A, 26B: a helper imports a product as given (no request, no merge, its dates), reads its fields back, and
 * retires or trashes it; a refused field comes out as an InvalidArgumentException naming it.
 */
final class ProductAccessTest extends TestCase {

	private InMemoryProductRepository $products;

	/** Dates written, by product. @var array<int, array{0: string, 1: string}> */
	private array $dates = array();

	private ProductAccess $access;

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'sanitize_text_field' )->alias( 'trim' );
		Functions\when( 'wp_kses' )->returnArg();
		Functions\when( 'esc_html' )->returnArg();
		$this->products = new InMemoryProductRepository();
		$fetcher        = new FakePageFetcher();
		$resolver       = new LinkResolver( $fetcher );
		$settings       = new InMemorySettingsRepository();
		$links          = new LinkNormaliser( $resolver, $settings );
		$service        = new ProductService( $this->products, $resolver, $settings, new ProductInput( $links ), $links, InMemoryAmazonProducts::without_amazon() );
		$this->access   = new ProductAccess(
			$service,
			$this->products,
			function ( int $id, string $created, string $modified ): void {
				$this->dates[ $id ] = array( $created, $modified );
			}
		);
	}

	public function test_an_import_is_a_new_product_each_time_with_its_dates_and_amazon_s_type_from_the_link(): void {
		$first  = $this->access->import( array( 'name' => 'Shoe', 'url' => 'https://www.amazon.com/dp/B0TEST0001?tag=vbvault-20', 'badge' => 'Best' ), '2025-01-02 03:04:05', '2025-02-03 04:05:06' );
		$second = $this->access->import( array( 'name' => 'Shoe', 'url' => 'https://www.amazon.com/dp/B0TEST0001?tag=vbvault-20' ), '', '' );

		self::assertNotSame( $first, $second, 'Each record is its own product (F10 D1).' );
		$product = $this->products->get( $first );
		self::assertSame( ProductType::Amazon, $product->type );
		self::assertSame( 'B0TEST0001', $product->asin );
		self::assertSame( 'https://www.amazon.com/dp/B0TEST0001?tag=vbvault-20', $product->url->value() );
		self::assertSame( array( '2025-01-02 03:04:05', '2025-02-03 04:05:06' ), $this->dates[ $first ] );
		self::assertArrayNotHasKey( $second, $this->dates, 'No dates given: WordPress keeps its own.' );
		self::assertSame( 'Best', $this->access->fields( $first )['badge'] );
		self::assertArrayNotHasKey( 'id', $this->access->fields( $first ) );
	}

	public function test_a_refused_field_names_itself(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'url: invalid_url' );
		$this->access->import( array( 'name' => 'Shoe', 'url' => 'not a link' ), '', '' );
	}

	public function test_an_address_is_set_and_one_another_product_answers_at_is_refused(): void {
		Functions\when( 'sanitize_title' )->alias( static fn ( string $title ): string => trim( (string) preg_replace( '/[^a-z0-9_]+/', '-', strtolower( $title ) ), '-' ) );
		$one = $this->access->import( array( 'name' => 'One', 'url' => 'https://a.example/' ), '', '' );
		$two = $this->access->import( array( 'name' => 'Two', 'url' => 'https://b.example/' ), '', '' );

		$this->access->set_address( $one, 'amzn-one-long', array( 'one', 'Old One', 'amzn-one-long' ), false );

		self::assertSame(
			array(
				'slug'      => 'amzn-one-long',
				'old_slugs' => array( 'one', 'old-one' ),
				'cloak'     => false,
				'retired'   => false,
			),
			$this->access->address( $one )
		);
		self::assertNull( $this->access->address( 999 ) );
		self::assertSame( $one, $this->access->holder( 'old-one' ) );
		self::assertSame( 0, $this->access->holder( 'nobody' ) );

		$this->expectException( InvalidArgumentException::class );
		$this->access->set_address( $two, 'two', array( 'old-one' ), true );
	}

	/**
	 * The cutover rehearsal's F2: a retired product's address still answers, so a helper reads and moves it like a
	 * published one's, and the product stays retired.
	 */
	public function test_a_retired_product_s_address_is_read_and_moved_and_it_stays_retired(): void {
		Functions\when( 'sanitize_title' )->alias( static fn ( string $title ): string => trim( (string) preg_replace( '/[^a-z0-9_]+/', '-', strtolower( $title ) ), '-' ) );
		$live = $this->access->import( array( 'name' => 'Neo Jump', 'url' => 'https://a.example/' ), '', '' );
		$old  = $this->access->import( array( 'name' => 'Neo Jump old', 'url' => 'https://b.example/' ), '', '' );
		$this->access->set_address( $old, 'neo-jump-2', array(), true );
		$this->access->retire( $old, $live );

		self::assertSame( $old, $this->access->holder( 'neo-jump-2' ), 'A retired product answers at its slug.' );
		self::assertTrue( $this->access->address( $old )['retired'] );

		$this->access->set_address( $old, 'neo-jump-gone', array( 'neo-jump-old' ), true );

		self::assertSame( 'neo-jump-gone', $this->access->address( $old )['slug'] );
		self::assertSame( array( 'neo-jump-old' ), $this->access->address( $old )['old_slugs'] );
		self::assertSame( $live, $this->products->repointed_to( $old ), 'Still retired, pointing at the live product.' );
		self::assertNull( $this->products->get( $old ), 'Not published again.' );
		self::assertSame( 0, $this->access->holder( 'neo-jump-2' ), 'Its old address is free for the live record.' );
	}

	public function test_a_product_is_retired_pointing_at_another_or_trashed(): void {
		$target = $this->access->import( array( 'name' => 'Target', 'url' => 'https://a.example/' ), '', '' );
		$old    = $this->access->import( array( 'name' => 'Old', 'url' => 'https://b.example/' ), '', '' );
		$gone   = $this->access->import( array( 'name' => 'Gone', 'url' => 'https://c.example/' ), '', '' );

		$this->access->retire( $old, $target );
		$this->access->trash( $gone );
		$this->access->date( $gone, '2024-05-06 07:08:09', '2024-06-07 08:09:10' );

		self::assertSame( $target, $this->products->repointed_to( $old ) );
		self::assertNull( $this->access->fields( 999 ) );
		self::assertSame( array( '2024-05-06 07:08:09', '2024-06-07 08:09:10' ), $this->dates[ $gone ] );
	}
}
