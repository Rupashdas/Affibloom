<?php
namespace Affibloom\Tests\Unit\Interface\Cli;

use Affibloom\Domain\Amazon\CatalogItem;
use Affibloom\Interface\Cli\AmazonCommand;
use Affibloom\Tests\Unit\Domain\Amazon\CatalogItemTest;
use PHPUnit\Framework\TestCase;

/**
 * wp affibloom amazon get (C1B, AC9): what it prints for an item.
 */
final class AmazonCommandTest extends TestCase {
	public function test_an_item_in_stock_prints_every_field(): void {
		self::assertSame(
			array(
				'ASIN: B0STUBITEM',
				'Title: Mikasa V200W Official Game Volleyball',
				'Image: https://m.media-amazon.com/images/I/71stubLarge.jpg',
				'Price: $69.95',
				'Savings: $10.04 (13%) off $79.99',
				'Availability: In stock (IN_STOCK: In Stock)',
				'URL: https://www.amazon.com/dp/B0STUBITEM?tag=vbvault-20&linkCode=ogi',
				'List price: $79.99 (LIST_PRICE)',
				'Seller: —',
				'Deal: —',
				'Brand: —',
				'Manufacturer: —',
				'Model: —',
				'Warranty: —',
				'Colour: —',
				'Size: —',
				'Dimensions: —',
				'EAN: —',
				'UPC: —',
				'Category: —',
				'Parent ASIN: B0PARENT01',
				'Images: 1',
				'Features:',
				'- FIVB approved for international play',
				'- Double-layer design for a soft touch',
			),
			AmazonCommand::lines( CatalogItem::from_creators( CatalogItemTest::recorded( 'getItems-ok.json' ) ) )
		);
	}

	public function test_the_full_data_of_a_real_answer_is_printed(): void {
		$lines = AmazonCommand::lines( CatalogItem::from_creators( CatalogItemTest::recorded( 'live-B000IXRCTQ-all.json' ) ) );
		foreach ( array(
			'List price: $42.99 (LIST_PRICE)',
			'Seller: Amazon.com (New, buy box)',
			'Deal: —',
			'Brand: Tachikara',
			'Manufacturer: Tachikara',
			'Model: SVMNC.GDW (part SVMNC.GDW)',
			'Warranty: 365-day Warranty, 1 time replacement',
			'Colour: Gold/White',
			'Size: Official',
			'Dimensions: height 3 inches, length 8 inches, weight 0.45 pounds, width 6 inches',
			'EAN: 0793917111029',
			'UPC: 793917111029',
			'Category: Indoor Volleyballs (#4); #1401 overall',
			'Parent ASIN: B000IXRDWW',
			'Images: 8',
		) as $line ) {
			self::assertContains( $line, $lines );
		}
	}

	public function test_empty_fields_print_a_dash(): void {
		$lines = AmazonCommand::lines( CatalogItem::from_creators( array( 'asin' => 'B0EMPTY001' ) ) );
		self::assertContains( 'Title: —', $lines );
		self::assertContains( 'Price: —', $lines );
		self::assertContains( 'Savings: —', $lines );
		self::assertContains( 'Availability: Out of stock', $lines );
		self::assertContains( 'Features: —', $lines );
	}
}
