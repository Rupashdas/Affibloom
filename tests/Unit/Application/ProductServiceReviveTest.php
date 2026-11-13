<?php
namespace Affibloom\Tests\Unit\Application;

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
 * A deleted product comes back when its link is added again (Task 17D; F1 requirements 11, X9, D42): Lasso's add and save
 * lookups match a trashed or retired record and publish it again. Adding answers as if the product were new; saving
 * another product with that link brings it back and is refused (S11).
 */
final class ProductServiceReviveTest extends TestCase {
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
		$fields = '' === $asin ? array( 'badge' => 'Our Pick' ) : array(
			'type'           => ProductType::Amazon,
			'asin'           => $asin,
			'amazon_country' => 'us',
		);

		return $this->repo->save( Product::draft( $name, AffiliateUrl::from_string( $url ), Settings::from_array( array() ) )->with( $fields ) );
	}

	public function test_adding_a_trashed_products_link_brings_it_back_as_if_new(): void {
		$gone = $this->product( 'Gone', 'https://www.example.com/gone' );
		$this->repo->trash( (int) $gone->id );

		$added = $this->service()->create_from_url( 'www.example.com/gone/' );

		self::assertSame( $gone->id, $added->product->id );
		self::assertFalse( $added->already_existed );
		self::assertSame( 'Gone', $this->repo->get( (int) $gone->id )?->name );
		self::assertSame( 'Our Pick', $this->repo->get( (int) $gone->id )?->badge );
		self::assertCount( 1, $this->repo->saved );
	}

	public function test_a_trashed_amazon_product_comes_back_by_its_asin(): void {
		$gone = $this->product( 'Mikasa', 'https://www.amazon.com/dp/B0C1234567?tag=vbvault-20', 'B0C1234567' );
		$this->repo->trash( (int) $gone->id );

		$added = $this->service()->create_from_url( 'https://www.amazon.com/Some-Words/dp/B0C1234567?tag=someone-20' );

		self::assertSame( $gone->id, $added->product->id );
		self::assertNotNull( $this->repo->get( (int) $gone->id ) );
	}

	public function test_a_retired_product_comes_back_without_its_pointer(): void {
		$retired = $this->product( 'Retired', 'https://www.example.com/retired' );
		$kept    = $this->product( 'Kept', 'https://www.example.com/kept' );
		$this->repo->retire( $retired, (int) $kept->id );

		$added = $this->service()->create_from_url( 'https://www.example.com/retired' );

		self::assertSame( $retired->id, $added->product->id );
		self::assertArrayNotHasKey( (int) $retired->id, $this->repo->retired );
	}

	public function test_a_published_product_wins_over_a_trashed_one(): void {
		$old = $this->product( 'Old', 'https://www.example.com/same' );
		$this->repo->trash( (int) $old->id );
		$new = $this->product( 'New', 'https://www.example.com/same' );

		$added = $this->service()->create_from_url( 'https://www.example.com/same' );

		self::assertSame( $new->id, $added->product->id );
		self::assertTrue( $added->already_existed );
		self::assertArrayHasKey( (int) $old->id, $this->repo->gone );
	}

	public function test_saving_a_trashed_products_link_brings_it_back_and_is_refused(): void {
		$mine = $this->product( 'Mine', 'https://www.example.com/mine' );
		$gone = $this->product( 'Gone', 'https://www.example.com/gone' );
		$this->repo->trash( (int) $gone->id );

		try {
			$this->service()->update( (int) $mine->id, array( 'url' => 'https://www.example.com/gone' ) );
			self::fail( 'The save must be refused.' );
		} catch ( ValidationFailed $refused ) {
			self::assertSame( array( 'url' => 'already_exists' ), $refused->errors );
		}
		self::assertNotNull( $this->repo->get( (int) $gone->id ) );
		self::assertSame( 'https://www.example.com/mine', $this->repo->get( (int) $mine->id )?->url->value() );
	}
}
