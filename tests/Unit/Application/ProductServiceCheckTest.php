<?php
namespace Affibloom\Tests\Unit\Application;

use Affibloom\Application\Products\LinkNormaliser;
use Affibloom\Application\Products\LinkResolver;
use Affibloom\Application\Products\ProductInput;
use Affibloom\Application\Products\ProductService;
use Affibloom\Tests\Unit\Fakes\FakePageFetcher;
use Affibloom\Tests\Unit\Fakes\InMemoryAmazonProducts;
use Affibloom\Tests\Unit\Fakes\InMemoryProductRepository;
use Affibloom\Tests\Unit\Fakes\InMemorySettingsRepository;
use Affibloom\Tests\Unit\TestCase;
use Brain\Monkey\Functions;

/**
 * Task 25A: check() reports what Save Changes would refuse, with update()'s rules, and stores nothing.
 */
final class ProductServiceCheckTest extends TestCase {

	private InMemoryProductRepository $products;
	private ProductService $service;

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'sanitize_text_field' )->alias( 'trim' );
		$this->products = new InMemoryProductRepository();
		$resolver       = new LinkResolver( new FakePageFetcher() );
		$settings       = new InMemorySettingsRepository();
		$links          = new LinkNormaliser( $resolver, $settings );
		$this->service  = new ProductService( $this->products, $resolver, $settings, new ProductInput( $links ), $links, InMemoryAmazonProducts::without_amazon() );
	}

	public function test_every_refusal_is_reported_together(): void {
		self::assertSame(
			array(
				'name' => 'required',
				'url'  => 'invalid_url',
			),
			$this->service->check(
				array(
					'name' => ' ',
					'url'  => 'not a link',
				)
			)
		);
		self::assertSame( array( 'url' => 'required' ), $this->service->check( array( 'url' => '' ) ) );
	}

	public function test_accepted_fields_give_no_refusal_and_nothing_is_stored(): void {
		self::assertSame(
			array(),
			$this->service->check(
				array(
					'name' => 'Mizuno Wave',
					'url'  => 'https://www.example.com/shoe',
				)
			)
		);
		self::assertSame( 0, $this->service->count() );
	}
}
