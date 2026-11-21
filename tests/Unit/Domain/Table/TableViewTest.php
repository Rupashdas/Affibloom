<?php
namespace Affibloom\Tests\Unit\Domain\Table;

use Affibloom\Domain\Table\Table;
use Affibloom\Domain\Table\TableProduct;
use Affibloom\Domain\Table\TableStyle;
use Affibloom\Domain\Table\TableView;
use PHPUnit\Framework\TestCase;

/**
 * The reader's Horizontal table (F25 R46; Task T12).
 */
final class TableViewTest extends TestCase {

	public function test_a_horizontal_table_has_a_header_per_group_and_a_row_per_product(): void {
		$table = new Table(
			1,
			'Shoes',
			TableStyle::Row,
			products: array( new TableProduct( 7, ' <span class="medal-icon"></span> Best ' ), new TableProduct( 8 ) ),
			groups: array(
				array( 'id' => 'g_a', 'fields' => array( 99991 ) ),
				array( 'id' => 'g_b', 'fields' => array( 99990, 500, 1 ) ),
				array( 'id' => 'g_c', 'fields' => array( 99992 ) ),
			)
		);

		$view = TableView::horizontal( $table, array( 99991 => 'Product Image', 99990 => 'Product name', 1 => 'primary rating', 99992 => 'Primary Button' ) );

		// Each word capitalised; a field without a name (500) left out of its header.
		self::assertSame( array( 'Product Image', 'Product Name / Primary Rating', 'Primary Button' ), $view->headers );
		self::assertSame( array( 7, 8 ), array_column( $view->rows, 'product' ) );
		self::assertSame( '<span class="medal-icon"></span> Best', $view->rows[0]['badge'] );
		self::assertSame( array( array( 99991 ), array( 99990, 500, 1 ), array( 99992 ) ), $view->rows[1]['cells'] );
	}

	/** Task T13 (F25 R45): a Vertical table has no header; each product's cells are its fields one by one. */
	public function test_a_vertical_table_has_a_cell_per_field_in_group_order(): void {
		$table = new Table(
			1,
			'Shoes',
			TableStyle::Column,
			products: array( new TableProduct( 7, 'Best' ), new TableProduct( 8 ) ),
			groups: array(
				array( 'id' => 'g_a', 'fields' => array( 99991 ) ),
				array( 'id' => 'g_b', 'fields' => array( 99990, 1 ) ),
			)
		);

		$view = TableView::vertical( $table );

		self::assertSame( array(), $view->headers );
		self::assertSame( array( array( 99991 ), array( 99990 ), array( 1 ) ), $view->rows[0]['cells'] );
		self::assertSame( array( 'Best', '' ), array_column( $view->rows, 'badge' ) );
	}
}
