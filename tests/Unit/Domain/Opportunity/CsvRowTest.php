<?php
namespace Affibloom\Tests\Unit\Domain\Opportunity;

use Affibloom\Domain\Opportunity\CsvRow;
use PHPUnit\Framework\TestCase;

/**
 * The Content Opportunities CSV's line (F22 R60, R61; Task O9).
 */
final class CsvRowTest extends TestCase {

	public function test_lassos_eighteen_headers_in_order(): void {
		self::assertCount( 18, CsvRow::HEADERS );
		self::assertSame( 'post_title', CsvRow::HEADERS[0] );
		self::assertSame( 'suggested_product_edit_url', CsvRow::HEADERS[17] );
	}

	public function test_cells_follow_the_headers_with_blanks_and_guards(): void {
		$cells = ( new CsvRow(
			array(
				'post_title'  => '=HYPERLINK("x")',
				'post_id'     => 7,
				'anchor_text' => 'Buy',
			)
		) )->cells();

		self::assertCount( 18, $cells );
		self::assertSame( "\t=HYPERLINK(\"x\")", $cells[0] );
		self::assertSame( '', $cells[1] );
		self::assertSame( '7', $cells[2] );
		self::assertSame( 'Buy', $cells[7] );
	}

	public function test_text_is_stripped_decoded_and_collapsed(): void {
		self::assertSame( 'Tom & Jerry’s best ball', CsvRow::text( "  <strong>Tom &amp; Jerry&#8217;s</strong>\n\t best <em>ball</em> " ) );
		self::assertSame( 'Read this.', CsvRow::text( 'Read <script>alert(1)</script>this.' ) );
	}

	public function test_an_amazon_domain_without_a_product_suggests_amazon_associates(): void {
		self::assertSame( 'Molten Ball', CsvRow::suggestion( 'Molten Ball', 'amazon.com' ) );
		self::assertSame( 'Amazon Associates', CsvRow::suggestion( '', 'amzn.to' ) );
		self::assertSame( '', CsvRow::suggestion( '', 'example.com' ) );
	}
}
