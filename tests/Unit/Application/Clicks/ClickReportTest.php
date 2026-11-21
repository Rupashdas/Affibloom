<?php
namespace Affibloom\Tests\Unit\Application\Clicks;

use Affibloom\Application\Clicks\ClickReport;
use Affibloom\Domain\Clicks\Button;
use Affibloom\Domain\Clicks\ClickStore;
use PHPUnit\Framework\TestCase;

/**
 * The 30-day figures (F12 R4, D8, AC10; Task 34A).
 */
final class ClickReportTest extends TestCase {

	public function test_each_product_gets_its_last_thirty_days_and_zero_when_none(): void {
		$store = new class() implements ClickStore {
			public string $since = '';

			public function add( string $day, int $product_id, int $post_id, int $location, Button $button ): void {}

			public function total_since( string $day ): int {
				return 'x' === $day ? 0 : 41;
			}

			public function by_location( string $day, array $location_ids ): array {
				return array( 3 => 4 );
			}

			public function delete_before( string $day ): void {}

			public function totals_since( string $day, array $product_ids ): array {
				$this->since = $day;
				return array( 7 => 12 );
			}
		};

		$totals = ( new ClickReport( $store ) )->totals_for( array( 9, 7 ), (int) gmmktime( 23, 0, 0, 11, 21, 2026 ) );

		// Today, 2026-11-21, and the 29 days before: from 2026-10-23.
		self::assertSame( '2026-10-23', $store->since );
		self::assertSame( array( 9 => 0, 7 => 12 ), $totals );
		self::assertSame( array(), ( new ClickReport( $store ) )->totals_for( array(), 0 ) );
		self::assertSame( 41, ( new ClickReport( $store ) )->total( 0 ) );
		self::assertSame( array( 2 => 0, 3 => 4 ), ( new ClickReport( $store ) )->by_location( array( 2, 3 ), 0 ) );
	}
}
