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
}
