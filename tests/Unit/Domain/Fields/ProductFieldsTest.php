<?php
namespace Affibloom\Tests\Unit\Domain\Fields;

use Affibloom\Domain\Fields\FieldLibrary;
use Affibloom\Domain\Fields\ProductField;
use Affibloom\Domain\Fields\ProductFields;
use PHPUnit\Framework\TestCase;

final class ProductFieldsTest extends TestCase {
	private FieldLibrary $library;

	protected function setUp(): void {
		parent::setUp();
		$this->library = FieldLibrary::from_array( array() );
	}

	/** Lasso's add: the rating starts at 3.5, every field visible, the name not shown (Field_Mapping::add_field_to_page). */
	public function test_an_added_field_starts_as_lassos(): void {
		$fields = ProductFields::from_array( array() )
			->attach( $this->library->find( 2 ) )
			->attach( $this->library->find( 1 ) );

		$rating = $fields->find( 1 );
		self::assertSame( '3.5', $rating?->value );
		self::assertTrue( $rating?->visible );
		self::assertFalse( $rating?->show_name );
		self::assertSame( '', $fields->find( 2 )?->value );
		// Primary Rating keeps the first place ("Position in display is fixed.").
		self::assertSame( array( 1, 2 ), $fields->ids() );
	}

	public function test_adding_a_field_twice_changes_nothing(): void {
		$fields = ProductFields::from_array( array() )->attach( $this->library->find( 2 ) );
		$fields = $fields->update( $this->library, array( new ProductField( 2, 'Grippy', true, false ) ) );

		self::assertSame( 'Grippy', $fields->attach( $this->library->find( 2 ) )->find( 2 )?->value );
	}

	/**
	 * Lasso's rating box: 1 to 5, one decimal; 0, nothing or not a number means 1 (url-details.php 906-916).
	 *
	 * @dataProvider ratings
	 */
	public function test_a_rating_is_kept_as_lassos_box_keeps_it( string $typed, string $kept ): void {
		$fields = ProductFields::from_array( array() )->attach( $this->library->find( 1 ) );

		self::assertSame( $kept, $fields->update( $this->library, array( new ProductField( 1, $typed, true, false ) ) )->find( 1 )?->value );
	}

	/** @return array<string, array{string, string}> */
	public static function ratings(): array {
		return array(
			'one decimal'  => array( '4.54', '4.5' ),
			'whole'        => array( '4', '4.0' ),
			'above five'   => array( '7', '5.0' ),
			'zero'         => array( '0', '1.0' ),
			'not a number' => array( 'great', '1.0' ),
		);
	}

	/** The editor's order is kept, except that Primary Rating stays first; unknown and doubled fields are ignored. */
	public function test_an_update_keeps_the_order_given_with_the_rating_first(): void {
		$fields = ProductFields::from_array( array() )
			->attach( $this->library->find( 1 ) )
			->attach( $this->library->find( 2 ) )
			->attach( $this->library->find( 3 ) );

		$updated = $fields->update(
			$this->library,
			array(
				new ProductField( 3, 'Heavy', false, false ),
				new ProductField( 2, "Grippy\nLight", true, false ),
				new ProductField( 1, '4.5', true, true ),
				new ProductField( 999, 'x', true, false ),
				new ProductField( 2, 'again', true, false ),
			)
		);

		self::assertSame( array( 1, 3, 2 ), $updated->ids() );
		self::assertFalse( $updated->find( 3 )?->visible );
		self::assertSame( "Grippy\nLight", $updated->find( 2 )?->value );
		self::assertTrue( $updated->find( 1 )?->show_name );
	}

	/** An update cannot add a field: only attach() does (Lasso's add and its save are separate requests). */
	public function test_an_update_leaves_out_fields_the_product_does_not_have(): void {
		$fields = ProductFields::from_array( array() )->attach( $this->library->find( 2 ) );

		self::assertSame( array( 2 ), $fields->update( $this->library, array( new ProductField( 3, 'x', true, false ), new ProductField( 2, 'y', true, false ) ) )->ids() );
	}

	public function test_a_field_is_removed_and_the_list_survives_storage(): void {
		$fields = ProductFields::from_array( array() )
			->attach( $this->library->find( 1 ) )
			->attach( $this->library->find( 2 ) );

		self::assertSame( array( 2 ), $fields->detach( 1 )->ids() );
		self::assertEquals( $fields, ProductFields::from_array( $fields->to_array() ) );
	}
}
