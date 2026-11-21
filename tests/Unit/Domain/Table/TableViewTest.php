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
}
