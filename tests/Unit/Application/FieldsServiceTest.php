<?php
namespace Affibloom\Tests\Unit\Application;

use Affibloom\Application\Fields\FieldsService;
use Affibloom\Domain\Fields\FieldLibrary;
use Affibloom\Domain\Fields\FieldRepository;
use Affibloom\Domain\Fields\ProductField;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Tests\Unit\Fakes\InMemoryFieldUsage;
use Affibloom\Tests\Unit\Fakes\InMemoryProductRepository;
use PHPUnit\Framework\TestCase;

final class FieldsServiceTest extends TestCase {
	private InMemoryProductRepository $products;
	private FieldRepository $library;
	private int $product;

	protected function setUp(): void {
		parent::setUp();
		$this->products = new InMemoryProductRepository();
		$this->library  = new class() implements FieldRepository {
			public ?FieldLibrary $stored = null;
			public function load(): FieldLibrary {
				return $this->stored ?? FieldLibrary::from_array( array() );
			}
			public function save( FieldLibrary $library ): void {
				$this->stored = $library;
			}
		};
		$this->product  = (int) $this->products->save( Product::draft( 'Shoe', AffiliateUrl::from_string( 'https://www.example.com/shoe' ), Settings::from_array( array() ) ) )->id;
	}

	private function service(): FieldsService {
		return new FieldsService( $this->library, $this->products, new InMemoryFieldUsage( $this->products ) );
	}

	public function test_a_created_field_is_stored_and_found_in_the_library(): void {
		$field = $this->service()->create( 'Sizes', 'bulleted_list', 'One size per line.' );

		self::assertSame( 100, $field->id );
		self::assertSame( array( 'Sizes' ), array_map( static fn( $f ) => $f->name, $this->service()->library( 'siz', 1 )->items ) );
	}

	public function test_an_unknown_type_is_refused(): void {
		$this->expectException( InvalidField::class );
		$this->service()->create( 'Sizes', 'colour', '' );
	}

	public function test_a_products_fields_are_added_saved_and_removed(): void {
		$service = $this->service();

		self::assertSame( array( 2 ), $service->attach( $this->product, 2 )?->ids() );
		// Primary Rating goes first, wherever it is added.
		$service->attach( $this->product, 1 );
		self::assertSame( array( 1, 2 ), $service->fields( $this->product )?->ids() );

		$service->update( $this->product, array( new ProductField( 2, 'Grippy', true, false ), new ProductField( 1, '9', false, true ) ) );
		self::assertSame( '5.0', $service->fields( $this->product )?->find( 1 )?->value );
		self::assertSame( 'Grippy', $service->fields( $this->product )?->find( 2 )?->value );

		self::assertSame( array( 2 ), $service->detach( $this->product, 1 )?->ids() );
	}

	/** The Fields list: every field with how many products have it; the dialog's library leaves the table fields out. */
	public function test_the_list_counts_each_fields_products_and_the_library_leaves_out_table_fields(): void {
		$service = $this->service();
		$service->attach( $this->product, 1 );
		$service->attach( $this->product, 2 );
		$other = (int) $this->products->save( Product::draft( 'Boot', AffiliateUrl::from_string( 'https://www.example.com/boot' ), Settings::from_array( array() ) ) )->id;
		$service->attach( $other, 1 );

		$listing = $service->list( '', 1 );
		self::assertSame( 9, $listing->page->total );
		self::assertSame( 2, $listing->usage->of( 1 ) );
		self::assertSame( 1, $listing->usage->of( 2 ) );
		self::assertSame( 0, $listing->usage->of( 3 ) );

		self::assertSame( 3, $service->library( '', 1 )->total );
		self::assertSame( 1, $service->list( 'pri rat', 1 )->page->total );
		self::assertSame( 0, $service->library( 'price', 1 )->total );
		self::assertSame( 1, $service->list( 'price', 1 )->page->total );
	}

	public function test_an_unknown_field_is_refused_and_a_missing_product_is_null(): void {
		self::assertNull( $this->service()->fields( 999 ) );
		self::assertNull( $this->service()->attach( 999, 1 ) );

		$this->expectException( InvalidField::class );
		$this->service()->attach( $this->product, 55 );
	}
}
