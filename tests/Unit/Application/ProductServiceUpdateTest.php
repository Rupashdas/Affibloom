<?php
namespace Affibloom\Tests\Unit\Application;

use Affibloom\Application\Products\LinkNormaliser;
use Affibloom\Application\Products\LinkResolver;
use Affibloom\Application\Products\ProductInput;
use Affibloom\Application\Products\ProductNotFound;
use Affibloom\Application\Products\ProductService;
use Affibloom\Application\Products\ValidationFailed;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Tests\Unit\Fakes\FakePageFetcher;
use Affibloom\Tests\Unit\Fakes\InMemoryProductRepository;
use Affibloom\Tests\Unit\Fakes\InMemorySettingsRepository;
use Affibloom\Tests\Unit\TestCase;

/**
 * Save Changes on the product page (Task 15A): all or nothing, the same product updated in place.
 */
final class ProductServiceUpdateTest extends TestCase {
	private InMemoryProductRepository $repo;

	protected function setUp(): void {
		parent::setUp();
		\Brain\Monkey\Functions\when( 'sanitize_text_field' )->alias( static fn( $s ) => trim( strip_tags( (string) $s ) ) );
		\Brain\Monkey\Functions\when( 'wp_kses' )->alias( static fn( $s, $allowed ) => strip_tags( (string) $s, array_keys( $allowed ) ) );
		$this->repo = new InMemoryProductRepository();
	}

	private function service(): ProductService {
		$settings = new InMemorySettingsRepository();
		$resolver = new LinkResolver( new FakePageFetcher() );
		$links    = new LinkNormaliser( $resolver, $settings );

		return new ProductService( $this->repo, $resolver, $settings, new ProductInput( $links ), $links );
	}

	private function stored(): Product {
		return $this->repo->save(
			Product::draft( 'Mizuno', AffiliateUrl::from_string( 'https://www.example.com/mizuno' ), Settings::from_array( array() ) )
				->with( array( 'description' => '<p><span style="color: rgb(20, 20, 20);">Grip</span></p>' ) )
		);
	}

	public function test_the_fields_sent_are_saved_on_the_same_product(): void {
		$product = $this->stored();

		$saved = $this->service()->update( (int) $product->id, array( 'name' => 'Mizuno Wave', 'badge' => 'Top Pick' ) );

		self::assertSame( $product->id, $saved->id );
		self::assertCount( 1, $this->repo->saved );
		self::assertSame( 'Mizuno Wave', $this->repo->get( (int) $product->id )?->name );
		self::assertSame( 'Top Pick', $this->repo->get( (int) $product->id )?->badge );
		// Not sent, so kept exactly as it was (F1 D19).
		self::assertSame( '<p><span style="color: rgb(20, 20, 20);">Grip</span></p>', $saved->description );
		self::assertSame( 'https://www.example.com/mizuno', $saved->url->value() );
	}

	public function test_a_refused_field_saves_nothing(): void {
		$product = $this->stored();

		try {
			$this->service()->update( (int) $product->id, array( 'name' => '', 'badge' => 'Top Pick' ) );
			self::fail( 'An empty name must be refused.' );
		} catch ( ValidationFailed $refused ) {
			self::assertSame( array( 'name' => 'required' ), $refused->errors );
		}
		self::assertSame( '', $this->repo->get( (int) $product->id )?->badge );
	}

	public function test_an_unknown_product_is_not_found(): void {
		$this->expectException( ProductNotFound::class );
		$this->service()->update( 999, array( 'name' => 'x' ) );
	}
}
