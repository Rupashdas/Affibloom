<?php
namespace Affibloom\Tests\Unit;

use Affibloom\Plugin;
use Brain\Monkey\Functions;

/**
 * Activation and deactivation (Task TL1, F37 AC1), from the static callbacks affibloom.php hands to
 * register_activation_hook() and register_deactivation_hook().
 */
final class LifecycleHooksTest extends TestCase {
	public function test_deactivating_clears_every_affibloom_schedule_and_flushes_rewrites(): void {
		Functions\expect( 'wp_clear_scheduled_hook' )->once()->with( 'affibloom_bulk_add' );
		Functions\expect( 'flush_rewrite_rules' )->once();
		Functions\expect( 'update_option' )->never();
		Functions\expect( 'delete_option' )->never();

		Plugin::deactivate();
	}

	public function test_activating_records_the_version_and_the_time_and_flushes_rewrites(): void {
		$stored = array();
		Functions\expect( 'update_option' )
			->twice()
			->andReturnUsing(
				static function ( string $name, mixed $value, ?bool $autoload = null ) use ( &$stored ): bool {
					$stored[ $name ] = array( $value, $autoload );
					return true;
				}
			);
		Functions\expect( 'flush_rewrite_rules' )->once();
		Functions\expect( 'wp_clear_scheduled_hook' )->never();
		Functions\expect( 'delete_option' )->never();
		// A site whose schema steps are all done (step 1 is Task 13G's), so activation runs none; the steps are
		// UpgraderTest's and MigratorTest's.
		Functions\when( 'get_option' )->alias(
			static fn( string $name, mixed $fallback = false ): mixed => 'affibloom_db_version' === $name ? PHP_INT_MAX : $fallback
		);

		$before = time();
		Plugin::activate();
		$after = time();

		self::assertSame( array( AFFIBLOOM_VERSION, null ), $stored['affibloom_version'] );
		// Lasso's lasso_plugin_activated_at: the time of the latest activation, not autoloaded.
		self::assertFalse( $stored['affibloom_activated_at'][1] );
		self::assertGreaterThanOrEqual( $before, $stored['affibloom_activated_at'][0] );
		self::assertLessThanOrEqual( $after, $stored['affibloom_activated_at'][0] );
	}
}
