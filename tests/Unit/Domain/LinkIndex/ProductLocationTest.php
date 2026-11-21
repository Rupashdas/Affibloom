<?php
namespace Affibloom\Tests\Unit\Domain\LinkIndex;

use Affibloom\Domain\LinkIndex\LocationPage;
use Affibloom\Domain\LinkIndex\ProductLocation;
use PHPUnit\Framework\TestCase;

/**
 * A row of the Locations tab and its pages (F12 L5, L7; Task 36B).
 */
final class ProductLocationTest extends TestCase {

	public function test_the_type_column_names_links_single_displays_and_group_layouts(): void {
		$kinds = array(
			'Text'                   => ProductLocation::TEXT,
			'Image-only anchor text' => ProductLocation::IMAGE,
			'Image'                  => ProductLocation::IMAGE,
			'Single'                 => ProductLocation::SINGLE,
			'Button'                 => ProductLocation::SINGLE,
			'Product Box'            => ProductLocation::SINGLE,
			'Grid'                   => ProductLocation::GRID,
			'List'                   => ProductLocation::LIST,
			'Gallery'                => ProductLocation::GALLERY,
		);
		foreach ( $kinds as $type => $kind ) {
			self::assertSame( $kind, ( new ProductLocation( 1, $type, 'https://example.com' ) )->kind(), $type );
		}
	}

	public function test_link_with_context_is_the_text_the_address_or_the_display(): void {
		self::assertSame( 'Buy it', ( new ProductLocation( 1, 'Text', 'https://example.com/a', 'Buy it' ) )->context() );
		self::assertSame( 'https://example.com/a', ( new ProductLocation( 1, 'Text', 'https://example.com/a' ) )->context() );
		self::assertSame( 'https://example.com/a', ( new ProductLocation( 1, 'Image-only anchor text', 'https://example.com/a', 'alt' ) )->context() );
		$grid = new ProductLocation( 1, 'Grid', '[affibloom type="grid" group="shoes"]', null, 'group:5' );
		self::assertSame( '[affibloom type="grid" group="shoes"]', $grid->context() );
		self::assertTrue( $grid->is_display() );
		self::assertFalse( ( new ProductLocation( 1, 'Text', 'https://example.com/a' ) )->is_display() );
	}

	public function test_pages_count_hosts_and_a_page_past_the_end_is_the_last(): void {
		$page = new LocationPage( array(), 23, 9 );

		self::assertSame( 3, $page->pages() );
		self::assertSame( 3, $page->current() );
		self::assertSame( 20, $page->offset() );
		self::assertSame( array( 21, 23 ), array( $page->first(), $page->last() ) );
		self::assertSame( array( 1, 0, 0 ), array( ( $empty = new LocationPage( array(), 0, 1 ) )->pages(), $empty->first(), $empty->offset() ) );
	}
}
