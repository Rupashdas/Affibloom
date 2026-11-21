<?php
namespace Affibloom\Tests\Unit\Domain\Table;

use Affibloom\Domain\Product\SearchTerm;
use Affibloom\Domain\Table\Table;
use Affibloom\Domain\Table\TableProduct;
use Affibloom\Domain\Table\TableSearch;
use Affibloom\Domain\Table\TableStyle;
use PHPUnit\Framework\TestCase;

/**
 * A comparison table's shape and the list's search (F25 R1, R7, R8, D1; Task T1).
 */
final class TableTest extends TestCase {

	public function test_a_new_table_is_vertical_cactus_with_headers_and_its_products_keep_their_order(): void {
		$table = new Table( null, 'Shoes', products: array( new TableProduct( 9 ), new TableProduct( 4, 'Best' ) ) );

		self::assertSame( TableStyle::Column, $table->style );
		self::assertSame( 'Cactus', $table->theme );
		self::assertTrue( $table->show_headers );
		self::assertSame( array( 9, 4 ), $table->product_ids() );
	}

	public function test_lassos_stored_style_is_read_in_any_case(): void {
		self::assertSame( TableStyle::Row, TableStyle::from_stored( 'Row' ) );
		self::assertSame( TableStyle::Column, TableStyle::from_stored( 'Column' ) );
		self::assertSame( TableStyle::Column, TableStyle::from_stored( '' ) );
	}

	public function test_the_search_finds_a_name_or_a_products_name_with_words_in_order(): void {
		self::assertTrue( TableSearch::matches( SearchTerm::from_input( '' ), 'Anything', array() ) );
		self::assertTrue( TableSearch::matches( SearchTerm::from_input( 'best ball' ), 'Best Volleyball', array() ) );
		self::assertFalse( TableSearch::matches( SearchTerm::from_input( 'ball best' ), 'Best Volleyball', array() ) );
		self::assertTrue( TableSearch::matches( SearchTerm::from_input( 'molten' ), 'Shoes', array( 'Molten FLISTATEC' ) ) );
		// R8's cleaning: a quote and a slash become spaces, so "molten's/v5" looks for "molten", "s" and "v5" in order.
		self::assertTrue( TableSearch::matches( SearchTerm::from_input( "molten's/v5" ), 'Shoes', array( 'Molten Sports V5M5000' ) ) );
		self::assertFalse( TableSearch::matches( SearchTerm::from_input( 'zzz' ), 'Shoes', array( 'Molten' ) ) );
	}

	/** Task T2 (F25 R12): Create Table's defaults and its refusal. */
	public function test_a_drafted_table_is_cactus_with_headers_and_field_names_and_needs_a_name(): void {
		$table = Table::draft( '  Shoes  ', TableStyle::Row );
		self::assertSame( array( null, 'Shoes', TableStyle::Row, 'Cactus', true, true, 1 ), array( $table->id, $table->name, $table->style, $table->theme, $table->show_headers, $table->show_field_name, $table->revision ) );

		$this->expectException( \Affibloom\Domain\InvalidField::class );
		Table::draft( '   ', TableStyle::Column );
	}

	/** A table with two products and Lasso's starting layout, for Task T6's rules. */
	private static function laid_out(): Table {
		$ids   = array( 'g_head', 'g_one', 'g_two' );
		$table = new Table( 1, 'Shoes' );
		foreach ( array( 7, 8 ) as $product ) {
			$table = $table->add_product( $product, '', static fn( int $field ): string => $product . ':' . $field, static function () use ( &$ids ): string {
				return (string) array_shift( $ids );
			} );
		}

		return $table;
	}

	/** Task T6 (F25 R25): Add Row needs a product and refuses a second empty one. */
	public function test_an_empty_line_needs_a_product_and_only_one_waits(): void {
		try {
			( new Table( 1, 'Shoes' ) )->add_group( 'g_x' );
			self::fail( 'An empty table took a row.' );
		} catch ( \Affibloom\Domain\Table\TableRuleBroken $broken ) {
			self::assertSame( 'no_products', $broken->rule );
		}
		$table = self::laid_out()->add_group( 'g_new' );
		self::assertSame( array( 'id' => 'g_new', 'fields' => array() ), $table->groups[3] );

		$this->expectExceptionObject( new \Affibloom\Domain\Table\TableRuleBroken( 'empty_group' ) );
		$table->add_group( 'g_other' );
	}

	/** Task T6 (F25 R2, R28, R31, D7): a field once per table, for every product; removed, its values stay. */
	public function test_a_field_is_added_for_every_product_once_and_its_values_survive_removal(): void {
		$table = self::laid_out()->add_group( 'g_new' )->add_field( 'g_new', 2, static fn( int $product, int $field ): string => "pros of $product" );
		self::assertSame( array( 2 ), $table->groups[3]['fields'] );
		self::assertSame( array( 'pros of 7', 'pros of 8' ), array( $table->values[7][2], $table->values[8][2] ) );

		try {
			$table->add_field( 'g_one', 2, static fn(): string => '' );
			self::fail( 'A field came twice.' );
		} catch ( \Affibloom\Domain\Table\TableRuleBroken $broken ) {
			self::assertSame( 'field_exists', $broken->rule );
		}

		$table = $table->set_value( 7, 2, 'Light' )->remove_field( 'g_new', 2 );
		self::assertCount( 3, $table->groups, 'The emptied row went.' );
		self::assertSame( 'Light', $table->values[7][2], 'D7: the value is kept.' );

		$back = $table->add_group( 'g_back' )->add_field( 'g_back', 2, static fn(): string => 'fresh' );
		self::assertSame( array( 'Light', 'pros of 8' ), array( $back->values[7][2], $back->values[8][2] ) );

		// The heading group stays even when emptied.
		self::assertSame( array(), $table->remove_field( 'g_head', 99991 )->groups[0]['fields'] );
	}

	/** Task T6 (F25 R30, R33): a cell must be in the table; a whole row goes, the heading row never. */
	public function test_cells_must_exist_and_a_whole_row_can_go(): void {
		$table = self::laid_out();
		self::assertSame( array( 'g_head', 'g_two' ), array_column( $table->remove_group( 'g_one' )->groups, 'id' ) );

		foreach ( array( static fn() => $table->remove_group( 'g_head' ), static fn() => $table->set_value( 7, 2, 'x' ), static fn() => $table->set_value( 99, 99990, 'x' ), static fn() => $table->remove_field( 'g_one', 99992 ) ) as $refused ) {
			try {
				$refused();
				self::fail( 'A missing row, field or cell was accepted.' );
			} catch ( \Affibloom\Domain\Table\TableRuleBroken $broken ) {
				self::assertContains( $broken->rule, array( 'group_missing', 'cell_missing', 'field_missing' ) );
			}
		}
	}
}
