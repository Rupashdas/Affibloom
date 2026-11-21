<?php
namespace Affibloom\Tests\Unit\Domain\Monetize;

use Affibloom\Application\Amazon\BannerDismissals;
use Affibloom\Application\Monetize\AmazonImportStatusService;
use Affibloom\Domain\LinkIndex\LinkIndexStats;
use Affibloom\Domain\Monetize\AmazonImportStatus;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Tests\Unit\Fakes\InMemoryLinkLocations;
use Affibloom\Tests\Unit\Fakes\InMemoryProductRepository;
use Affibloom\Tests\Unit\Fakes\InMemorySettingsRepository;
use PHPUnit\Framework\TestCase;

/**
 * The import's card, General figures and Dashboard notice (F35 R32-R34, D19, D21; F35 AC7; Task AM5).
 */
final class AmazonImportStatusContextTest extends TestCase {

	public function test_the_context_shows_while_busy_or_on_the_first_day_with_more_placements(): void {
		self::assertTrue( ( new AmazonImportStatus( 3, 7, 1 ) )->shows_context( false ) );
		self::assertTrue( ( new AmazonImportStatus( 0, 7, 3015 ) )->shows_context( true ) );
		self::assertFalse( ( new AmazonImportStatus( 0, 7, 3015 ) )->shows_context( false ) );
		self::assertFalse( ( new AmazonImportStatus( 0, 7, 7 ) )->shows_context( true ) );
	}

	private function service( bool $on, int $activated, InMemoryLinkLocations $rows ): AmazonImportStatusService {
		$settings         = new InMemorySettingsRepository();
		$settings->stored = Settings::from_array( array( 'auto_monetize_amazon' => $on ) );
		$dismissals       = new class() implements BannerDismissals {
			public array $by = array();
			public function get( int $user ): string {
				return $this->by[ $user ] ?? '';
			}
			public function set( int $user, string $fingerprint ): void {
				$this->by[ $user ] = $fingerprint;
			}
		};

		return new AmazonImportStatusService( $rows, new InMemoryProductRepository(), $settings, $dismissals, static fn(): array => array(), static fn(): int => $activated );
	}

	public function test_the_dashboard_notice_is_the_first_days_idle_explanation_until_dismissed(): void {
		$now  = 1_800_000_000;
		$rows          = new InMemoryLinkLocations();
		$rows->figures = new LinkIndexStats( links: 3015 );
		$fresh = $this->service( true, $now - 3600, $rows );

		self::assertNotNull( $fresh->dashboard_notice( 1, $now ) );
		$fresh->dismiss( 1 );
		self::assertNull( $fresh->dashboard_notice( 1, $now ) );
		self::assertNull( $this->service( true, $now - 2 * 86400, $rows )->dashboard_notice( 2, $now ), 'after the first day' );
		self::assertNull( $this->service( false, $now - 3600, $rows )->dashboard_notice( 2, $now ), 'with the setting off' );

		self::assertNotNull( $fresh->general( $now ) );
		self::assertNull( $this->service( true, $now - 2 * 86400, $rows )->general( $now ) );
		self::assertNotNull( $fresh->card() );
		self::assertNull( $this->service( false, $now, $rows )->card() );
	}
}
