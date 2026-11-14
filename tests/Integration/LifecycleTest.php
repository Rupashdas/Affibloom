<?php
namespace Affibloom\Tests\Integration;

use Affibloom\Plugin;
use WP_UnitTestCase;

/**
 * Activation and deactivation (Task TL1, F37 AC1). Runs only where wp-env is available (progress.md, deviation 10).
 */
final class LifecycleTest extends WP_UnitTestCase {
	public function test_deactivating_leaves_no_affibloom_schedule(): void {
		wp_schedule_single_event( time() + 60, 'affibloom_bulk_add' );
		wp_schedule_single_event( time() + 60, 'someone_elses_event' );

		Plugin::deactivate();

		self::assertFalse( wp_next_scheduled( 'affibloom_bulk_add' ) );
		self::assertNotFalse( wp_next_scheduled( 'someone_elses_event' ) );
	}

	public function test_activating_records_the_version_and_time_and_keeps_products_and_settings(): void {
		delete_option( 'affibloom_version' );
		delete_option( 'affibloom_activated_at' );
		update_option( 'affibloom_settings', array( 'kept' => true ) );
		$product = self::factory()->post->create( array( 'post_type' => 'affibloom_product' ) );

		$before = time();
		Plugin::activate();

		self::assertSame( AFFIBLOOM_VERSION, get_option( 'affibloom_version' ) );
		self::assertGreaterThanOrEqual( $before, (int) get_option( 'affibloom_activated_at' ) );
		self::assertSame( array( 'kept' => true ), get_option( 'affibloom_settings' ) );
		self::assertNotNull( get_post( $product ) );
	}
}
