<?php
namespace Affibloom\Tests\Unit\Domain\LinkHealth;

use Affibloom\Domain\LinkHealth\CheckResult;
use Affibloom\Domain\LinkHealth\IssueType;
use Affibloom\Domain\LinkHealth\LinkHealthPolicy;
use Affibloom\Domain\LinkHealth\LinkIssue;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * Every transition of a product's row after a check (F11 R13, D4, D6; Task 30A).
 */
final class LinkHealthPolicyTest extends TestCase {

	private const URL = 'https://shop.example/item';

	private static function time( string $time ): DateTimeImmutable {
		return new DateTimeImmutable( "2026-11-18 {$time}" );
	}

	private static function apply( ?LinkIssue $current, CheckResult $result, string $time, string $url = self::URL ): ?LinkIssue {
		return LinkHealthPolicy::apply( $current, 7, $url, $result, self::time( $time ) );
	}

	public function test_a_first_200_stores_a_checked_row(): void {
		$row = self::apply( null, CheckResult::ok(), '03:00' );

		$this->assertSame( IssueType::OK, $row->issue_type );
		$this->assertTrue( $row->resolved );
		$this->assertFalse( $row->is_open() );
		$this->assertNull( $row->detection_date );
		$this->assertSame( 0, $row->failures );
	}

	public function test_one_failure_is_a_suspect_not_an_issue(): void {
		$row = self::apply( null, CheckResult::failed( 404 ), '03:00' );

		$this->assertSame( IssueType::NONE, $row->issue_type );
		$this->assertFalse( $row->is_open() );
		$this->assertSame( 1, $row->failures );
		$this->assertSame( '404', $row->pending_type );
		$this->assertEquals( self::time( '03:00' ), $row->first_failed_at );
	}

	public function test_a_second_failure_in_a_row_opens_the_issue_dated_by_the_first(): void {
		$first  = self::apply( null, CheckResult::failed( 503 ), '03:00' );
		$second = self::apply( $first, CheckResult::failed( 404 ), '04:05' );

		$this->assertTrue( $second->is_open() );
		$this->assertSame( '404', $second->issue_type );
		$this->assertEquals( self::time( '03:00' ), $second->detection_date );
		$this->assertSame( 0, $second->failures );
		$this->assertNull( $second->pending_type );
		$this->assertEquals( self::time( '04:05' ), $second->last_checked_at );
	}

	public function test_a_second_failure_within_the_hour_changes_nothing(): void {
		$first = self::apply( null, CheckResult::failed( 404 ), '03:00' );

		$this->assertNull( self::apply( $first, CheckResult::failed( 404 ), '03:59' ) );
		$this->assertTrue( self::apply( $first, CheckResult::failed( 404 ), '04:00' )->is_open() );
	}

	public function test_a_200_between_two_failures_resets_the_count(): void {
		$row = self::apply( null, CheckResult::failed( 404 ), '03:00' );
		$row = self::apply( $row, CheckResult::ok(), '04:00' );
		$row = self::apply( $row, CheckResult::failed( 404 ), '05:00' );

		$this->assertFalse( $row->is_open() );
		$this->assertSame( 1, $row->failures );
	}

	public function test_a_200_resolves_an_open_issue_and_keeps_its_type_and_date(): void {
		$open = self::apply( self::apply( null, CheckResult::failed( 404 ), '01:00' ), CheckResult::failed( 404 ), '02:00' );
		$row  = self::apply( $open, CheckResult::ok(), '03:00' );

		$this->assertFalse( $row->is_open() );
		$this->assertTrue( $row->resolved );
		$this->assertSame( '404', $row->issue_type );
		$this->assertEquals( self::time( '01:00' ), $row->detection_date );
		$this->assertEquals( self::time( '03:00' ), $row->resolved_at );
	}

	public function test_breaking_again_after_resolved_takes_two_checks_and_a_new_date(): void {
		$open     = self::apply( self::apply( null, CheckResult::failed( 404 ), '01:00' ), CheckResult::failed( 404 ), '02:00' );
		$resolved = self::apply( $open, CheckResult::ok(), '03:00' );

		$suspect = self::apply( $resolved, CheckResult::failed( 410 ), '10:00' );
		$this->assertFalse( $suspect->is_open() );
		$this->assertTrue( $suspect->resolved );

		$again = self::apply( $suspect, CheckResult::failed( 410 ), '11:00' );
		$this->assertTrue( $again->is_open() );
		$this->assertSame( '410', $again->issue_type );
		$this->assertEquals( self::time( '10:00' ), $again->detection_date );
		$this->assertNull( $again->resolved_at );
	}

	public function test_a_failing_open_issue_stays_open_with_its_date(): void {
		$open = self::apply( self::apply( null, CheckResult::failed( 404 ), '01:00' ), CheckResult::failed( 404 ), '02:00' );
		$row  = self::apply( $open, CheckResult::failed( 500 ), '03:00' );

		$this->assertTrue( $row->is_open() );
		$this->assertSame( '500', $row->issue_type );
		$this->assertEquals( self::time( '01:00' ), $row->detection_date );
		$this->assertSame( 0, $row->failures );
	}

	public function test_a_certain_answer_opens_at_once(): void {
		$row = self::apply( null, CheckResult::certain( IssueType::NOT_FOUND ), '03:00', '' );

		$this->assertTrue( $row->is_open() );
		$this->assertSame( '404', $row->issue_type );
		$this->assertEquals( self::time( '03:00' ), $row->detection_date );
		$this->assertSame( 0, $row->failures );
	}

	public function test_an_out_of_stock_answer_opens_at_once_and_keeps_an_open_date(): void {
		$open = self::apply( null, CheckResult::certain( IssueType::OUT_OF_STOCK ), '03:00' );
		$row  = self::apply( $open, CheckResult::certain( IssueType::OUT_OF_STOCK ), '09:00' );

		$this->assertSame( '000', $row->issue_type );
		$this->assertEquals( self::time( '03:00' ), $row->detection_date );
	}

	public function test_a_changed_destination_starts_the_count_again(): void {
		$first = self::apply( null, CheckResult::failed( 404 ), '03:00' );
		$row   = self::apply( $first, CheckResult::failed( 404 ), '04:00', 'https://shop.example/other' );

		$this->assertFalse( $row->is_open() );
		$this->assertSame( 1, $row->failures );
		$this->assertEquals( self::time( '04:00' ), $row->first_failed_at );
		$this->assertSame( 'https://shop.example/other', $row->checked_url );
	}

	public function test_a_request_not_sent_changes_nothing(): void {
		$this->assertNull( self::apply( null, CheckResult::not_executed(), '03:00' ) );
		$this->assertNull( self::apply( self::apply( null, CheckResult::failed( 404 ), '03:00' ), CheckResult::not_executed(), '04:00' ) );
	}

	public function test_an_answer_outside_the_status_range_counts_as_no_answer(): void {
		$this->assertSame( '500', CheckResult::failed( 0 )->type );
		$this->assertSame( '418', CheckResult::failed( 418 )->type );
	}
}
