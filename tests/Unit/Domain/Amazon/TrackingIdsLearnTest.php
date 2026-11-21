<?php
namespace Affibloom\Tests\Unit\Domain\Amazon;

use Affibloom\Application\Amazon\TrackingIdLearning;
use Affibloom\Domain\Amazon\TrackingIds;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Tests\Unit\Fakes\InMemorySettingsRepository;
use PHPUnit\Framework\TestCase;

/**
 * Lasso's auto-whitelist (F35 R27, D16; F35 AC4; Task AM6).
 */
final class TrackingIdsLearnTest extends TestCase {

	private static function settings( array $changes = array() ): Settings {
		return Settings::from_array(
			$changes + array(
				'tracking_id'                    => 'vbvault-20',
				'multiple_tracking_ids'          => true,
				'force_change_from_link_details' => false,
				'tracking_id_whitelist'          => array( 'vbvault-20' ),
				'tracking_id_blacklist'          => array(),
			)
		);
	}

	public function test_a_partners_tag_joins_the_whitelist(): void {
		self::assertSame( array( 'vbvault-20', 'partner-21' ), TrackingIds::learn( 'partner-21', self::settings() ) );
	}

	public function test_nothing_changes_under_each_of_the_five_conditions(): void {
		self::assertNull( TrackingIds::learn( 'partner-21', self::settings( array( 'multiple_tracking_ids' => false ) ) ), 'Multiple IDs off' );
		self::assertNull( TrackingIds::learn( 'partner-21', self::settings( array( 'force_change_from_link_details' => true ) ) ), 'Force changes on' );
		self::assertNull( TrackingIds::learn( 'partner-21', self::settings( array( 'tracking_id_blacklist' => array( 'partner-21' ) ) ) ), 'removed by hand' );
		self::assertNull( TrackingIds::learn( 'vbvault-20', self::settings( array( 'tracking_id_whitelist' => array() ) ) ), "the site's own" );
		self::assertNull( TrackingIds::learn( 'vbvault-20', self::settings() ), 'already whitelisted' );
		self::assertNull( TrackingIds::learn( 'maas', self::settings() ) );
		self::assertNull( TrackingIds::learn( ' ', self::settings() ) );
	}

	public function test_the_service_saves_once_for_the_tags_that_joined(): void {
		$repo         = new InMemorySettingsRepository();
		$repo->stored = self::settings();

		self::assertSame( array( 'partner-21', 'other-20' ), ( new TrackingIdLearning( $repo ) )->learn( array( 'partner-21', 'vbvault-20', 'other-20', 'partner-21' ) ) );
		self::assertSame( array( 'vbvault-20', 'partner-21', 'other-20' ), $repo->stored->list( 'tracking_id_whitelist' ) );
	}
}
