<?php
namespace Affibloom\Tests\Unit\Domain\Product;

use Affibloom\Domain\Product\ProductPage;
use PHPUnit\Framework\TestCase;

final class ProductPageTest extends TestCase {

	public function test_the_pages_are_counted_by_rounding_up(): void {
		self::assertSame( 27, $this->page( 1, 10, 263 )->pages() );
		self::assertSame( 11, $this->page( 1, 25, 263 )->pages() );
		self::assertSame( 1, $this->page( 1, 10, 10 )->pages() );
	}

	public function test_an_empty_list_still_has_one_page(): void {
		self::assertSame( 1, $this->page( 1, 10, 0 )->pages() );
	}

	public function test_the_range_on_the_first_page(): void {
		$page = $this->page( 1, 10, 263 );

		self::assertSame( 1, $page->first() );
		self::assertSame( 10, $page->last() );
	}

	public function test_the_range_on_a_later_page(): void {
		$page = $this->page( 2, 10, 263 );

		self::assertSame( 11, $page->first() );
		self::assertSame( 20, $page->last() );
	}

	public function test_the_last_page_stops_at_the_total(): void {
		$page = $this->page( 27, 10, 263 );

		self::assertSame( 261, $page->first() );
		self::assertSame( 263, $page->last() );
	}

	/** Lasso's range function clamps a page past the end to the last one (view-js.php, lasso_update_link_range). */
	public function test_a_page_past_the_end_shows_the_last_pages_range(): void {
		$page = $this->page( 99, 10, 263 );

		self::assertSame( 261, $page->first() );
		self::assertSame( 263, $page->last() );
	}

	public function test_a_list_shorter_than_one_page(): void {
		$page = $this->page( 1, 10, 3 );

		self::assertSame( 1, $page->first() );
		self::assertSame( 3, $page->last() );
	}

	public function test_an_empty_list_has_no_range(): void {
		$page = new ProductPage( array(), 0, 1, 10 );

		self::assertSame( 0, $page->first() );
		self::assertSame( 0, $page->last() );
		self::assertTrue( $page->is_empty() );
	}

	/**
	 * A page with no products on it: first() and last() are arithmetic over the total, as Lasso's range is,
	 * so they never look at the items.
	 *
	 * @param int $number   Page number.
	 * @param int $per_page Products per page.
	 * @param int $total    Products in the whole list.
	 */
	private function page( int $number, int $per_page, int $total ): ProductPage {
		return new ProductPage( array(), $total, $number, $per_page );
	}
}
