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
				'Features:',
				'- FIVB approved for international play',
				'- Double-layer design for a soft touch',
			),
			AmazonCommand::lines( CatalogItem::from_creators( CatalogItemTest::recorded( 'getItems-ok.json' ) ) )
		);
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
