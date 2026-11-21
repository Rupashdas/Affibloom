<?php
namespace Affibloom\Tests\Unit\Domain\Analytics;

use Affibloom\Domain\Analytics\GaSettings;
use Affibloom\Domain\Settings\Settings;
use PHPUnit\Framework\TestCase;

/**
 * Google Analytics as the site uses it (C3 R3, R4, R6; Task C3A).
 */
final class GaSettingsTest extends TestCase {

	public function test_the_ids_are_a_trimmed_comma_list_and_any_format_is_accepted(): void {
		$ga = GaSettings::of( Settings::from_array( array( 'ga_tracking_id' => ' G-ABC123 , UA-1-1,, 371244810 ', 'ga_click_tracking' => true, 'ga_pageview' => true, 'ga_anonymize_ip' => false ) ) );

		self::assertSame( array( 'G-ABC123', 'UA-1-1', '371244810' ), $ga->ids );
		self::assertTrue( $ga->clicks );
		self::assertTrue( $ga->pageview );
		self::assertFalse( $ga->anonymize );
	}

	public function test_click_tracking_needs_an_id_and_the_others_need_click_tracking(): void {
		$no_id = GaSettings::of( Settings::from_array( array( 'ga_tracking_id' => '  ', 'ga_click_tracking' => true, 'ga_pageview' => true ) ) );
		$off   = GaSettings::of( Settings::from_array( array( 'ga_tracking_id' => 'G-1', 'ga_click_tracking' => false, 'ga_pageview' => true, 'ga_anonymize_ip' => true ) ) );

		self::assertSame( array( array(), false, false ), array( $no_id->ids, $no_id->clicks, $no_id->pageview ) );
		self::assertSame( array( false, false, false ), array( $off->clicks, $off->pageview, $off->anonymize ) );
	}
}
