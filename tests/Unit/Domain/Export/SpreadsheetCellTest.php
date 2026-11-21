<?php
namespace Affibloom\Tests\Unit\Domain\Export;

use Affibloom\Domain\Export\SpreadsheetCell;
use PHPUnit\Framework\TestCase;

/**
 * Lasso's formula guard (F11 R28, F22 R61; Tasks 31D, O9).
 */
final class SpreadsheetCellTest extends TestCase {

	public function test_cells_that_a_spreadsheet_would_run_are_guarded(): void {
		$this->assertSame( "\t=SUM(A1)", SpreadsheetCell::guard( '=SUM(A1)' ) );
		$this->assertSame( "\t+cmd", SpreadsheetCell::guard( '+cmd' ) );
		$this->assertSame( "\t@x", SpreadsheetCell::guard( '@x' ) );
		$this->assertSame( "\t-x", SpreadsheetCell::guard( '-x' ) );
		$this->assertSame( '-5', SpreadsheetCell::guard( '-5' ) );
		$this->assertSame( 'Plain', SpreadsheetCell::guard( 'Plain' ) );
		$this->assertSame( '', SpreadsheetCell::guard( '' ) );
	}

	public function test_leading_spaces_do_not_hide_a_formula(): void {
		$this->assertSame( "\t  =1+1", SpreadsheetCell::guard( '  =1+1' ) );
		$this->assertSame( ' -5', SpreadsheetCell::guard( ' -5' ) );
	}
}
