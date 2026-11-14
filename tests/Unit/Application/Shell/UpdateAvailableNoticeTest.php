<?php
namespace Affibloom\Tests\Unit\Application\Shell;

use Affibloom\Application\Shell\UpdateAvailableNotice;
use Affibloom\Application\Shell\UpdateSource;
use Affibloom\Domain\Shell\ShellNotice;
use PHPUnit\Framework\TestCase;

/**
 * F38 R24, D11, AC6: when WordPress lists an update of Affibloom (for someone who may update plugins), every page says
 * so with WordPress's update link; the notice has no × (Lasso's has none).
 */
final class UpdateAvailableNoticeTest extends TestCase {

	private static function source( bool $available ): UpdateSource {
		return new class( $available ) implements UpdateSource {
			public function __construct( private bool $available ) {}
			public function available(): bool {
				return $this->available;
			}
			public function update_url(): string {
				return 'https://example.test/wp-admin/update.php?action=upgrade-plugin&plugin=affibloom%2Faffibloom.php&_wpnonce=abc';
			}
		};
	}

	public function test_an_available_update_is_announced_with_its_link(): void {
		$notices = ( new UpdateAvailableNotice( self::source( true ) ) )->notices( 'affibloom' );

		self::assertCount( 1, $notices );
		self::assertSame( 'update_available', $notices[0]->key );
		self::assertSame( ShellNotice::UPDATE, $notices[0]->tone );
		self::assertSame( 'https://example.test/wp-admin/update.php?action=upgrade-plugin&plugin=affibloom%2Faffibloom.php&_wpnonce=abc', $notices[0]->action_url );
		self::assertFalse( $notices[0]->dismissible );
	}

	public function test_no_update_no_notice(): void {
		self::assertSame( array(), ( new UpdateAvailableNotice( self::source( false ) ) )->notices( 'affibloom-settings' ) );
	}
}
