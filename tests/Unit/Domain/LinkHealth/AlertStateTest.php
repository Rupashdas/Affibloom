<?php
namespace Affibloom\Tests\Unit\Domain\LinkHealth;

use Affibloom\Domain\LinkHealth\AlertState;
use Affibloom\Domain\LinkHealth\AlertWindow;
use Affibloom\Domain\LinkHealth\LinkIssue;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * The Dashboard's Alert cell (Task 31A; F11 R21, R22, R27, D13, D14; AC8, AC11) and the grace days.
 */
final class AlertStateTest extends TestCase {

	private static function now(): DateTimeImmutable {
		return new DateTimeImmutable( '2026-11-18 12:00:00' );
	}

	private static function row( string $type, bool $resolved, ?string $since = '2026-11-16 08:00:00', int $failures = 0 ): LinkIssue {
		return new LinkIssue( 7, $type, 'https://shop.example', null === $since ? null : new DateTimeImmutable( $since ), $resolved, null, self::now(), $failures );
	}

	public function test_a_broken_product_shows_since_when_and_the_days(): void {
		$state = AlertState::of( self::row( '404', false ), false, new AlertWindow( 0 ), self::now() );

		$this->assertSame( AlertState::BROKEN, $state->kind );
		$this->assertSame( 'Nov 16, 2026', $state->since() );
		$this->assertSame( 3, $state->days );
	}

	public function test_the_day_of_detection_is_day_one(): void {
		$state = AlertState::of( self::row( '500', false, '2026-11-18 01:00:00' ), false, new AlertWindow( 0 ), self::now() );

		$this->assertSame( 1, $state->days );
	}

	public function test_out_of_stock_shows_only_after_the_grace_days(): void {
		$row = self::row( '000', false );

		$this->assertSame( AlertState::OUT_OF_STOCK, AlertState::of( $row, false, new AlertWindow( 2 ), self::now() )->kind );
		$this->assertSame( AlertState::NONE, AlertState::of( $row, false, new AlertWindow( 3 ), self::now() )->kind );
		$this->assertTrue( ( new AlertWindow( 2 ) )->shows( $row, self::now() ) );
		$this->assertFalse( ( new AlertWindow( 3 ) )->shows( $row, self::now() ) );
		$this->assertSame( '2026-11-15 12:00:00', ( new AlertWindow( 3 ) )->cutoff( self::now() )->format( 'Y-m-d H:i:s' ) );
	}

	public function test_a_checked_product_with_no_issue_gets_the_tick(): void {
		$this->assertSame( AlertState::CHECKED, AlertState::of( self::row( '200', true, null ), false, new AlertWindow( 0 ), self::now() )->kind );
		$this->assertSame( AlertState::CHECKED, AlertState::of( self::row( '404', true ), false, new AlertWindow( 0 ), self::now() )->kind );
	}

	public function test_a_new_unchecked_product_is_processing_and_an_old_one_shows_nothing(): void {
		$this->assertSame( AlertState::PENDING, AlertState::of( null, true, new AlertWindow( 0 ), self::now() )->kind );
		$this->assertSame( AlertState::NONE, AlertState::of( null, false, new AlertWindow( 0 ), self::now() )->kind );
		// A first failure waiting for its second check is not a result yet.
		$this->assertSame( AlertState::PENDING, AlertState::of( self::row( '', false, null, 1 ), true, new AlertWindow( 0 ), self::now() )->kind );
	}

	public function test_a_hidden_out_of_stock_issue_is_not_a_tick(): void {
		$this->assertSame( AlertState::NONE, AlertState::of( self::row( '000', false ), false, new AlertWindow( 30 ), self::now() )->kind );
	}

	public function test_the_window_clamps_lassos_range(): void {
		$this->assertSame( 30, ( new AlertWindow( 99 ) )->days );
		$this->assertSame( 0, ( new AlertWindow( -4 ) )->days );
	}
}
