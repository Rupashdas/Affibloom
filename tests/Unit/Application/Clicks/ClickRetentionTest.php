<?php
namespace Affibloom\Tests\Unit\Application\Clicks;

use Affibloom\Application\Clicks\ClickRetention;
use Affibloom\Domain\Clicks\Button;
use Affibloom\Domain\Clicks\ClickStore;
use PHPUnit\Framework\TestCase;

/**
 * Click rows are kept 400 days (F12 AC8, ADR-005; Task 33C).
 */
final class ClickRetentionTest extends TestCase {

	public function test_the_day_four_hundred_days_ago_is_the_first_kept(): void {
		$store = new class() implements ClickStore {
			public string $before = '';

			public function add( string $day, int $product_id, int $post_id, int $location, Button $button ): void {}

			public function totals_since( string $day, array $product_ids ): array {
				return array();
			}

			public function total_since( string $day ): int {
				return 'x' === $day ? 0 : 41;
			}

			public function delete_before( string $day ): void {
				$this->before = $day;
			}
		};

		( new ClickRetention( $store ) )->run( (int) gmmktime( 3, 0, 0, 11, 21, 2026 ) );

		// 2026-11-21 minus 400 days: 2025-10-17 (400 days old) stays, 2025-10-16 (401) goes.
		self::assertSame( '2025-10-17', $store->before );
	}
}
