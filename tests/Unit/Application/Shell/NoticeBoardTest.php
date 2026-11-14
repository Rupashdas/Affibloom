<?php
namespace Affibloom\Tests\Unit\Application\Shell;

use Affibloom\Application\Shell\NoticeBoard;
use Affibloom\Application\Shell\NoticeProvider;
use Affibloom\Domain\Shell\ShellNotice;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

/**
 * F38 D8, D10 and edge case 1: one region, filled by registered providers in order; a provider that fails is skipped
 * and reported, and the page still renders.
 */
final class NoticeBoardTest extends TestCase {

	private static function provider( ShellNotice ...$notices ): NoticeProvider {
		return new class( $notices ) implements NoticeProvider {
			/** @var list<string> */
			public array $pages = array();
			public function __construct( private array $notices ) {}
			public function notices( string $page ): array {
				$this->pages[] = $page;
				return $this->notices;
			}
		};
	}

	public function test_the_notices_come_in_the_providers_order(): void {
		$update  = new ShellNotice( 'update_available', ShellNotice::UPDATE );
		$failure = new ShellNotice( 'upgrade_failed', ShellNotice::ERROR, array( 1, 'x' ) );
		$first   = self::provider( $update );
		$board   = new NoticeBoard( array( $first, self::provider(), self::provider( $failure ) ), static function (): void {} );

		self::assertSame( array( $update, $failure ), $board->for_page( 'affibloom-groups' ) );
		self::assertSame( array( 'affibloom-groups' ), $first->pages );
	}

	public function test_a_failing_provider_is_skipped_and_reported(): void {
		$kept    = new ShellNotice( 'update_available', ShellNotice::UPDATE );
		$failing = new class() implements NoticeProvider {
			public function notices( string $page ): array {
				throw new RuntimeException( 'the option is broken' );
			}
		};
		$reported = array();
		$board    = new NoticeBoard(
			array( $failing, self::provider( $kept ) ),
			static function ( Throwable $error ) use ( &$reported ): void {
				$reported[] = $error->getMessage();
			}
		);

		self::assertSame( array( $kept ), $board->for_page( 'affibloom' ) );
		self::assertSame( array( 'the option is broken' ), $reported );
	}

	public function test_a_notice_has_one_of_the_regions_tones(): void {
		$this->expectException( InvalidArgumentException::class );
		new ShellNotice( 'update_available', 'purple' );
	}
}
