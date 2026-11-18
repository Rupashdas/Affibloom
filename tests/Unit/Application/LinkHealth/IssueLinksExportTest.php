<?php
namespace Affibloom\Tests\Unit\Application\LinkHealth;

use Affibloom\Application\LinkHealth\IssueLinksExport;
use Affibloom\Domain\LinkHealth\LinkIssue;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Button;
use Affibloom\Domain\Product\Buttons;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Settings\Settings;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * The broken and out-of-stock lists as Lasso's WP All Import CSV (Task 31D; F11 R28, D18; AC13).
 */
final class IssueLinksExportTest extends TestCase {

	private static function product( int $id, array $fields = array() ): Product {
		return Product::draft( 'Mizuno Wave', AffiliateUrl::from_string( 'https://shop.example/w' ), Settings::from_array( array() ) )
			->with( array( 'id' => $id, 'slug' => 'mizuno-wave' ) + $fields );
	}

	private static function issue( int $id, string $type, bool $open ): LinkIssue {
		return new LinkIssue( $id, $type, 'x', new DateTimeImmutable( '2026-11-01' ), ! $open, null, new DateTimeImmutable() );
	}

	public function test_the_header_is_lassos_24_columns(): void {
		$this->assertCount( 24, IssueLinksExport::COLUMNS );
		$this->assertSame( 'title', IssueLinksExport::COLUMNS[0] );
		$this->assertSame( 'alert_status', IssueLinksExport::COLUMNS[22] );
		$this->assertSame( 'location_count', IssueLinksExport::COLUMNS[23] );
	}

	public function test_a_row_carries_the_products_values_and_its_alert(): void {
		$product = self::product(
			7,
			array(
				'button_text' => 'Buy',
				'buttons'     => new Buttons( array( new Button( 'https://shop.example/2', 'More', false, true ) ) ),
				'badge'       => 'Best',
				'show_price'  => null,
				'asin'        => '',
			)
		);
		$row = IssueLinksExport::row( $product, self::issue( 7, '404', true ), array( 'Balls', 'Shoes' ), 'text >> Size >> 10', 3 );

		$this->assertCount( 24, $row );
		$this->assertSame( 'Mizuno Wave', $row[0] );
		$this->assertSame( 'mizuno-wave', $row[1] );
		$this->assertSame( 'Balls, Shoes', $row[2] );
		$this->assertSame( 'text >> Size >> 10', $row[3] );
		$this->assertSame( 'https://shop.example/w', $row[4] );
		$this->assertSame( 'Buy', $row[6] );
		$this->assertSame( 'https://shop.example/2', $row[9] );
		$this->assertSame( 'More', $row[10] );
		$this->assertSame( '0', $row[11] );
		$this->assertSame( '1', $row[12] );
		$this->assertSame( 'Best', $row[14] );
		$this->assertSame( '', $row[16] );
		$this->assertSame( 'Broken (404)', $row[22] );
		$this->assertSame( '3', $row[23] );
	}

	public function test_alert_status_follows_lassos_words(): void {
		$this->assertSame( 'Out of stock', IssueLinksExport::row( self::product( 1 ), self::issue( 1, '000', true ), array(), '', 0 )[22] );
		$this->assertSame( 'Checked', IssueLinksExport::row( self::product( 1 ), self::issue( 1, '404', false ), array(), '', 0 )[22] );
		$this->assertSame( '', IssueLinksExport::row( self::product( 1 ), null, array(), '', 0 )[22] );
	}

	public function test_cells_that_a_spreadsheet_would_run_are_guarded(): void {
		$this->assertSame( "\t=SUM(A1)", IssueLinksExport::guard( '=SUM(A1)' ) );
		$this->assertSame( "\t+cmd", IssueLinksExport::guard( '+cmd' ) );
		$this->assertSame( "\t@x", IssueLinksExport::guard( '@x' ) );
		$this->assertSame( "\t-x", IssueLinksExport::guard( '-x' ) );
		$this->assertSame( '-5', IssueLinksExport::guard( '-5' ) );
		$this->assertSame( 'Plain', IssueLinksExport::guard( 'Plain' ) );
	}
}
