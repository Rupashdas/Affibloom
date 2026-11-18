<?php
namespace Affibloom\Tests\Unit\Application\LinkHealth;

use Affibloom\Application\LinkHealth\LinkCheckJobStatus;
use Affibloom\Domain\LinkHealth\CheckPass;
use Affibloom\Domain\LinkHealth\CheckPassRepository;
use PHPUnit\Framework\TestCase;

/**
 * The link check in the header's jobs (Task 32E; F11 R36, D23; AC18).
 */
final class LinkCheckJobStatusTest extends TestCase {

	private static function status( CheckPass $pass ): LinkCheckJobStatus {
		return new LinkCheckJobStatus(
			new class( $pass ) implements CheckPassRepository {
				public function __construct( private CheckPass $pass ) {}

				public function get(): CheckPass {
					return $this->pass;
				}

				public function save( CheckPass $pass ): void {
					$this->pass = $pass;
				}
			}
		);
	}

	public function test_a_running_pass_shows_lassos_name_and_its_progress(): void {
		$progress = self::status( new CheckPass( '2026-11-19', 129, 80, array( 1, 2 ) ) )->progress();

		$this->assertNotNull( $progress );
		$this->assertSame( 'Checking for broken links', $progress->name );
		$this->assertSame( 80, $progress->done );
		$this->assertSame( 129, $progress->total );
	}

	public function test_a_finished_pass_is_not_running(): void {
		$this->assertNull( self::status( new CheckPass( '2026-11-19', 129, 129, array() ) )->progress() );
	}
}
