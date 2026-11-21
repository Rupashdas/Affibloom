<?php
namespace Affibloom\Tests\Unit\Application;

use Affibloom\Application\Monetize\AffiliateDetector;
use Affibloom\Application\Products\LinkNormaliser;
use Affibloom\Application\Products\LinkResolver;
use Affibloom\Application\Products\ProductInput;
use Affibloom\Application\Products\ProductService;
use Affibloom\Domain\LinkIndex\LinkLocation;
use Affibloom\Domain\Monetize\NetworkRegistry;
use Affibloom\Tests\Unit\Fakes\FakePageFetcher;
use Affibloom\Tests\Unit\Fakes\InMemoryAmazonProducts;
use Affibloom\Tests\Unit\Fakes\InMemoryDetectedLinks;
use Affibloom\Tests\Unit\Fakes\InMemoryProductRepository;
use Affibloom\Tests\Unit\Fakes\InMemorySettingsRepository;
use PHPUnit\Framework\TestCase;

/**
 * Products for detected affiliate links (F35 R13, R14, D5, D7; C2 AC1, AC2, AC8; Task C2C).
 */
final class AffiliateDetectorCreateTest extends TestCase {

	private InMemoryProductRepository $products;

	private InMemoryDetectedLinks $detected;

	private AffiliateDetector $detector;

	protected function setUp(): void {
		$this->products = new InMemoryProductRepository();
		$this->detected = new InMemoryDetectedLinks();
		$resolver       = new LinkResolver( new FakePageFetcher() );
		$settings       = new InMemorySettingsRepository();
		$links          = new LinkNormaliser( $resolver, $settings );
		$service        = new ProductService( $this->products, $resolver, $settings, new ProductInput( $links ), $links, InMemoryAmazonProducts::without_amazon() );
		$this->detector = new AffiliateDetector( NetworkRegistry::from_array( require dirname( __DIR__, 3 ) . '/resources/networks.php' ), $this->detected, $service );
	}

	private function detect( string $url ): void {
		$this->detector->detect( array( new LinkLocation( 7, 'https://site.example/p/', 'content', 1, 0, 'external', 'Text', $url, md5( $url ) ) ) );
	}

	public function test_a_detected_link_becomes_a_product_with_its_link_as_written_and_cloaking_off(): void {
		$url = 'https://mizunousa.sjv.io/jR4ZQP';
		$this->detect( $url );

		self::assertSame( 1, $this->detector->create_products( 100, static fn(): bool => true ) );

		$product = $this->products->saved[ array_key_first( $this->products->saved ) ];
		self::assertSame( $url, $product->url->value() );
		self::assertFalse( $product->cloak );
		self::assertSame( (int) $product->id, $this->detected->product_for( $url ) );
	}

	public function test_a_second_run_makes_nothing_and_a_gone_product_is_made_again(): void {
		$this->detect( 'https://mizunousa.sjv.io/QYX4zz' );
		$this->detector->create_products( 100, static fn(): bool => true );

		self::assertSame( 0, $this->detector->create_products( 100, static fn(): bool => true ) );
		self::assertCount( 1, $this->products->saved );

		$this->detected->gone[] = $this->detected->product_for( 'https://mizunousa.sjv.io/QYX4zz' );
		self::assertSame( 1, $this->detector->create_products( 100, static fn(): bool => true ) );
	}

	public function test_the_run_stops_when_the_setting_is_switched_off(): void {
		$this->detect( 'https://mizunousa.sjv.io/X4RGbo' );

		self::assertSame( 0, $this->detector->create_products( 100, static fn(): bool => false ) );
		self::assertSame( array(), $this->products->saved );
	}
}
