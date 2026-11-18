<?php
namespace Affibloom\Tests\Unit\Application\LinkHealth;

use Affibloom\Application\LinkHealth\LinkCheckService;
use Affibloom\Application\LinkHealth\LinkStatusChecker;
use Affibloom\Application\Products\BackgroundJob;
use Affibloom\Domain\LinkHealth\CheckPass;
use Affibloom\Domain\LinkHealth\CheckPassRepository;
use Affibloom\Domain\LinkHealth\CheckResult;
use Affibloom\Tests\Unit\Fakes\InMemoryLinkIssues;
use Affibloom\Tests\Unit\Fakes\InMemorySettingsRepository;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * The daily pass and its batches (F11 R3, R6, R9, D2, D4-D6, D10, D11; Task 30A).
 */
final class LinkCheckServiceTest extends TestCase {

	private InMemoryLinkIssues $issues;

	private CheckPassRepository $passes;

	/** @var array<string, CheckResult> URL => answer; others answer 200. */
	private array $answers = array();

	/** @var list<string> */
	private array $requested = array();

	/** @var list<int> Each dispatch's delay. */
	private array $dispatched = array();

	/** @var list<array{int, string, string}> */
	private array $detected = array();

	private DateTimeImmutable $now;

	private InMemorySettingsRepository $settings;

	protected function setUp(): void {
		$this->issues   = new InMemoryLinkIssues();
		$this->now      = new DateTimeImmutable( '2026-11-18 03:00:00' );
		$this->settings = new InMemorySettingsRepository();
		$this->passes   = new class() implements CheckPassRepository {
			public CheckPass $pass;

			public function __construct() {
				$this->pass = CheckPass::from_array( null );
			}

			public function get(): CheckPass {
				return $this->pass;
			}

			public function save( CheckPass $pass ): void {
				$this->pass = $pass;
			}
		};
	}

	private function service(): LinkCheckService {
		$test    = $this;
		$checker = new class( $test ) implements LinkStatusChecker {
			public function __construct( private LinkCheckServiceTest $test ) {}

			public function status( string $url ): CheckResult {
				return $this->test->answer( $url );
			}
		};
		$job     = new class( $test ) implements BackgroundJob {
			public function __construct( private LinkCheckServiceTest $test ) {}

			public function dispatch( int $delay = 0 ): void {
				$this->test->dispatched( $delay );
			}
		};

		return new LinkCheckService(
			$this->issues,
			$this->passes,
			$checker,
			$job,
			$this->settings,
			fn(): DateTimeImmutable => $this->now,
			function ( int $id, string $url, string $type ): void {
				$this->detected[] = array( $id, $url, $type );
			}
		);
	}

	public function answer( string $url ): CheckResult {
		$this->requested[] = $url;
		return $this->answers[ $url ] ?? CheckResult::ok();
	}

	public function dispatched( int $delay ): void {
		$this->dispatched[] = $delay;
	}

	public function test_the_pass_queues_every_due_product_once_a_day(): void {
		$this->issues->destinations = array( 3 => 'https://a.example', 5 => 'https://b.example' );

		$this->assertTrue( $this->service()->start_pass( '2026-11-18' ) );
		$this->assertSame( array( 3, 5 ), $this->passes->get()->queue );
		$this->assertSame( 2, $this->passes->get()->total );
		$this->assertSame( 1, $this->issues->orphans_deleted );
		$this->assertSame( array( 0 ), $this->dispatched );

		$this->assertFalse( $this->service()->start_pass( '2026-11-18' ) );
		$this->assertSame( array( 0 ), $this->dispatched );
		$this->assertTrue( $this->service()->start_pass( '2026-11-19' ) );
	}

	public function test_the_pass_follows_the_locations_only_setting(): void {
		$this->service()->start_pass( '2026-11-18' );
		$this->assertTrue( $this->issues->locations_only );

		$this->settings->stored = $this->settings->stored->with( array( 'pricing_locations_only' => false ) );
		$this->service()->start_pass( '2026-11-19' );
		$this->assertFalse( $this->issues->locations_only );
	}

	public function test_a_batch_checks_at_most_its_limit_and_chains_the_next(): void {
		$this->issues->destinations = array( 1 => 'https://a.example', 2 => 'https://b.example', 3 => 'https://c.example' );
		$this->service()->start_pass( '2026-11-18' );
		$this->dispatched = array();

		$this->service()->run_batch( 2 );
		$this->assertSame( array( 3 ), $this->passes->get()->queue );
		$this->assertSame( 2, $this->passes->get()->checked );
		$this->assertSame( array( 0 ), $this->dispatched );

		$this->service()->run_batch( 2 );
		$this->assertSame( array(), $this->passes->get()->queue );
		$this->assertSame( 3, $this->passes->get()->checked );
		$this->assertSame( array( 0 ), $this->dispatched, 'Nothing left: no next batch.' );
		$this->assertSame( '200', $this->issues->get( 3 )->issue_type );
	}

	public function test_the_time_budget_is_checked_between_requests(): void {
		$this->issues->destinations = array( 1 => 'https://a.example', 2 => 'https://b.example' );
		$this->service()->start_pass( '2026-11-18' );

		$this->service()->run_batch( 100, 0.0 );
		$this->assertSame( array( 'https://a.example' ), $this->requested );
		$this->assertSame( array( 2 ), $this->passes->get()->queue );
	}

	public function test_a_blank_destination_is_broken_without_a_request(): void {
		$this->issues->destinations = array( 4 => '  ' );
		$this->service()->start_pass( '2026-11-18' );
		$this->service()->run_batch();

		$this->assertSame( array(), $this->requested );
		$this->assertTrue( $this->issues->get( 4 )->is_open() );
		$this->assertSame( '404', $this->issues->get( 4 )->issue_type );
		$this->assertSame( array( array( 4, '', '404' ) ), $this->detected );
	}

	public function test_a_product_no_longer_due_is_skipped(): void {
		$this->issues->destinations = array( 1 => 'https://a.example', 2 => 'https://b.example' );
		$this->service()->start_pass( '2026-11-18' );
		unset( $this->issues->destinations[1] ); // Trashed, or became an Amazon product, during the pass.

		$this->service()->run_batch();
		$this->assertSame( array( 'https://b.example' ), $this->requested );
		$this->assertNull( $this->issues->get( 1 ) );
		$this->assertSame( array(), $this->passes->get()->queue );
	}

	public function test_a_failure_is_checked_again_an_hour_later_and_then_reported(): void {
		$this->issues->destinations = array( 1 => 'https://dead.example' );
		$this->answers['https://dead.example'] = CheckResult::failed( 404 );
		$this->service()->start_pass( '2026-11-18' );
		$this->dispatched = array();

		$this->service()->run_batch();
		$this->assertFalse( $this->issues->get( 1 )->is_open() );
		$this->assertSame( array( 3600 ), $this->dispatched, 'The second check waits an hour.' );
		$this->assertSame( array(), $this->detected );

		$this->now = $this->now->modify( '+30 minutes' );
		$this->service()->run_batch();
		$this->assertCount( 1, $this->requested, 'Not yet an hour.' );
		$this->assertSame( 1800, end( $this->dispatched ) );

		$this->now = $this->now->modify( '+31 minutes' );
		$this->service()->run_batch();
		$this->assertCount( 2, $this->requested );
		$this->assertTrue( $this->issues->get( 1 )->is_open() );
		$this->assertSame( array( array( 1, 'https://dead.example', '404' ) ), $this->detected );
	}

	public function test_a_suspect_whose_request_was_not_sent_does_not_loop(): void {
		$this->issues->destinations = array( 1 => 'https://dead.example' );
		$this->answers['https://dead.example'] = CheckResult::failed( 404 );
		$this->service()->start_pass( '2026-11-18' );
		$this->service()->run_batch();

		$this->answers['https://dead.example'] = CheckResult::not_executed();
		$this->now        = $this->now->modify( '+2 hours' );
		$this->dispatched = array();
		$this->service()->run_batch();

		$this->assertSame( 1, $this->issues->get( 1 )->failures );
		$this->assertSame( array(), $this->dispatched, 'Tomorrow\'s pass tries it again.' );
	}
}
