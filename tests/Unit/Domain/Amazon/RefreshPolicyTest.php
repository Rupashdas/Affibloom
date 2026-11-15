<?php
namespace Affibloom\Tests\Unit\Domain\Amazon;

use Affibloom\Domain\Amazon\RefreshPolicy;
use PHPUnit\Framework\TestCase;

/**
 * Lasso's scheduled refresh rules (C1 R37, R38; C1Q4b): the batch per run and the 24-hour age.
 */
final class RefreshPolicyTest extends TestCase {

	public function test_the_batch_is_a_twentieth_of_the_due_clamped_to_100_500_and_never_more_than_due(): void {
		self::assertSame( array( 0, 7, 100, 100, 150, 500 ), array_map( array( RefreshPolicy::class, 'batch' ), array( 0, 7, 100, 2000, 3000, 20000 ) ) );
	}

	public function test_data_is_due_after_24_hours(): void {
		self::assertSame( '2026-11-14 12:00:00', RefreshPolicy::cutoff( strtotime( '2026-11-15 12:00:00 UTC' ) ) );
	}
}
