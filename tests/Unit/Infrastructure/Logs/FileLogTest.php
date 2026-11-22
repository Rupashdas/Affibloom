<?php
namespace Affibloom\Tests\Unit\Infrastructure\Logs;

use Affibloom\Domain\Logs\LogType;
use Affibloom\Infrastructure\Logs\FileLog;
use Affibloom\Tests\Unit\TestCase;

/**
 * F37 R3-R6, AC4 (Task TL3): one file per type and day, the newest shown, forced lines when logging is off, 14 days
 * kept; each file opens with a PHP exit and the folder holds an index.php and a deny-all .htaccess.
 */
final class FileLogTest extends TestCase {

	private string $dir;

	protected function setUp(): void {
		parent::setUp();
		$this->dir = sys_get_temp_dir() . '/affibloom-logs-' . uniqid();
	}

	protected function tearDown(): void {
		foreach ( (array) glob( $this->dir . '/{,.}*', GLOB_BRACE ) as $file ) {
			if ( is_file( (string) $file ) ) {
				unlink( (string) $file );
			}
		}
		if ( is_dir( $this->dir ) ) {
			rmdir( $this->dir );
		}
		parent::tearDown();
	}

	private function log( bool $enabled, int $now ): FileLog {
		return new FileLog( $this->dir, static fn(): bool => $enabled, static fn(): int => $now );
	}

	public function test_a_line_is_written_to_the_day_s_file_of_its_type_and_shown_as_the_newest(): void {
		$day = gmmktime( 9, 5, 7, 11, 22, 2026 );
		$this->log( true, $day )->write( LogType::UrlIssueCheck, 'https://www.example.com/a: 404' );

		self::assertFileExists( $this->dir . '/2026_11_22_url_issue_check.log.php' );
		self::assertStringStartsWith( '<?php exit; ?>', (string) file_get_contents( $this->dir . '/2026_11_22_url_issue_check.log.php' ) );
		self::assertSame( "[22/Nov/2026:09:05:07] https://www.example.com/a: 404\n", $this->log( true, $day )->newest( LogType::UrlIssueCheck ) );
		self::assertSame( '', $this->log( true, $day )->newest( LogType::SqlErrors ) );
		self::assertFileExists( $this->dir . '/index.php' );
		self::assertStringContainsString( 'Require all denied', (string) file_get_contents( $this->dir . '/.htaccess' ) );
	}

	public function test_the_newest_file_of_the_exact_type_is_shown(): void {
		$this->log( true, gmmktime( 0, 0, 0, 11, 20, 2026 ) )->write( LogType::Amazon, 'older' );
		$this->log( true, gmmktime( 0, 0, 0, 11, 21, 2026 ) )->write( LogType::Amazon, 'newer' );
		$this->log( true, gmmktime( 0, 0, 0, 11, 22, 2026 ) )->write( LogType::AmazonApiError, 'other type' );

		self::assertStringContainsString( 'newer', $this->log( true, 0 )->newest( LogType::Amazon ) );
		self::assertStringNotContainsString( 'other type', $this->log( true, 0 )->newest( LogType::Amazon ) );
	}

	public function test_with_logging_off_only_forced_lines_are_written(): void {
		$now = gmmktime( 0, 0, 0, 11, 22, 2026 );
		$this->log( false, $now )->write( LogType::LinkDatabase, 'not kept' );
		$this->log( false, $now )->write( LogType::SqlErrors, 'kept', true );

		self::assertSame( '', $this->log( false, $now )->newest( LogType::LinkDatabase ) );
		self::assertStringContainsString( 'kept', $this->log( false, $now )->newest( LogType::SqlErrors ) );
	}

	public function test_logs_older_than_14_days_are_deleted(): void {
		$this->log( true, gmmktime( 0, 0, 0, 11, 1, 2026 ) )->write( LogType::Amazon, 'old' );
		$this->log( true, gmmktime( 0, 0, 0, 11, 9, 2026 ) )->write( LogType::Amazon, 'recent' );

		self::assertSame( 1, $this->log( true, 0 )->prune( gmmktime( 12, 0, 0, 11, 22, 2026 ) ) );
		self::assertFileDoesNotExist( $this->dir . '/2026_11_01_amazon.log.php' );
		self::assertFileExists( $this->dir . '/2026_11_09_amazon.log.php' );
	}
}
