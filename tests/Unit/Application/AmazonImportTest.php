<?php
namespace Affibloom\Tests\Unit\Application;

use Affibloom\Application\Monetize\AmazonImport;
use Affibloom\Application\Products\LinkNormaliser;
use Affibloom\Application\Products\LinkResolver;
use Affibloom\Application\Products\ProductInput;
use Affibloom\Application\Products\ProductService;
use Affibloom\Tests\Unit\Fakes\FakePageFetcher;
use Affibloom\Tests\Unit\Fakes\InMemoryAmazonProducts;
use Affibloom\Tests\Unit\Fakes\InMemoryLinkLocations;
use Affibloom\Tests\Unit\Fakes\InMemoryProductRepository;
use Affibloom\Tests\Unit\Fakes\InMemorySettingsRepository;
use PHPUnit\Framework\TestCase;

/**
 * The Amazon import (F35 R24, R25, D11, D13; F35 AC1, AC8; Task AM1).
 */
final class AmazonImportTest extends TestCase {

	private InMemoryProductRepository $products;

	private InMemoryLinkLocations $rows;

	private AmazonImport $import;

	protected function setUp(): void {
		$this->products = new InMemoryProductRepository();
		$this->rows     = new InMemoryLinkLocations();
		$resolver       = new LinkResolver( new FakePageFetcher() );
		$settings       = new InMemorySettingsRepository();
		$links          = new LinkNormaliser( $resolver, $settings );
		$service        = new ProductService( $this->products, $resolver, $settings, new ProductInput( $links ), $links, InMemoryAmazonProducts::without_amazon() );
		$this->import   = new AmazonImport( $this->rows, $service, $this->products );
	}

	private static function link( string $asin, string $tag = 'other-20' ): array {
		return array(
			'asin'    => $asin,
			'country' => 'us',
			'domain'  => 'amazon.com',
			'tag'     => $tag,
		);
	}

	public function test_each_queued_asin_becomes_an_amazon_product_and_its_rows_are_tied(): void {
		$this->rows->amazon = array( self::link( 'B0F35AC1XX' ) );

		self::assertSame( array( 'queued' => 1, 'done' => 1, 'made' => 1 ), $this->import->run( 100, static fn(): bool => true ) );

		$product = $this->products->saved[ array_key_first( $this->products->saved ) ];
		self::assertSame( 'B0F35AC1XX', $product->asin );
		self::assertSame( array( array( 'B0F35AC1XX', 'us', (int) $product->id ) ), $this->rows->tied );
	}

	public function test_an_asin_that_already_is_a_product_makes_nothing_new(): void {
		$this->rows->amazon = array( self::link( 'B0F35AC2XX' ) );
		$this->import->run( 100, static fn(): bool => true );

		self::assertSame( 0, $this->import->run( 100, static fn(): bool => true )['made'] );
		self::assertCount( 1, $this->products->saved );
	}

	public function test_a_retired_asin_is_skipped(): void {
		$this->rows->amazon = array( self::link( 'B0F35AC3XX' ) );
		$this->import->run( 100, static fn(): bool => true );
		$product = $this->products->saved[ array_key_first( $this->products->saved ) ];
		$this->products->retire( $product, 0 );
		$this->rows->tied = array();

		self::assertSame( 0, $this->import->run( 100, static fn(): bool => true )['made'] );
		self::assertSame( array(), $this->rows->tied );
	}

	public function test_the_run_stops_when_the_setting_is_switched_off(): void {
		$this->rows->amazon = array( self::link( 'B0F35AC4XX' ), self::link( 'B0F35AC5XX' ) );
		$calls              = 0;

		$batch = $this->import->run( 100, static function () use ( &$calls ): bool {
			return 1 === ++$calls;
		} );

		self::assertSame( array( 'queued' => 2, 'done' => 1, 'made' => 1 ), $batch );
	}
}
