<?php
namespace Affibloom\Tests\Unit\Interface;

use Affibloom\Application\Lifecycle;
use Affibloom\Application\PluginState;
use Affibloom\Application\Schedules;
use Affibloom\Application\Upgrade\Migrations;
use Affibloom\Application\Upgrade\StepFailed;
use Affibloom\Application\Upgrade\UpgradeLock;
use Affibloom\Application\Upgrade\Upgrader;
use Affibloom\Interface\UpgradeHooks;
use Affibloom\Tests\Unit\TestCase;
use Brain\Monkey\Functions;

/**
 * The upgrade's entry points on a request (Task TL2, F37 AC2): admin pages run pending steps (not AJAX or cron, as
 * Lasso's Update_DB), and init flushes rewrite rules once when the plugin version changes.
 */
final class UpgradeHooksTest extends TestCase {
	public int $runs = 0;
	public ?StepFailed $fails = null;
	public string $stored_version = '0.0.0-test';
	/** @var list<string> */
	public array $state = array();

	private function hooks(): UpgradeHooks {
		$test       = $this;
		$migrations = new class( $test ) implements Migrations {
			public function __construct( private UpgradeHooksTest $test ) {}
			public function pending(): bool {
				return true;
			}
			public function run(): array {
				++$this->test->runs;
				if ( null !== $this->test->fails ) {
					throw $this->test->fails;
				}
				return array( 1 );
			}
		};
		$lock       = new class() implements UpgradeLock {
			public function acquire(): bool {
				return true;
			}
			public function release(): void {}
		};
		$state      = new class( $test ) implements PluginState {
			public function __construct( private UpgradeHooksTest $test ) {}
			public function stored_version(): string {
				return $this->test->stored_version;
			}
			public function record_version( string $version ): void {
				$this->test->state[] = 'version ' . $version;
			}
			public function record_activation_time(): void {
				$this->test->state[] = 'activated';
			}
			public function flush_rewrite_rules(): void {
				$this->test->state[] = 'flush';
			}
		};
		$schedules  = new class() implements Schedules {
			public function clear(): void {}
		};
		$upgrader   = new Upgrader( $lock, $migrations );
		return new UpgradeHooks( $upgrader, new Lifecycle( $schedules, $state, $upgrader, '0.0.0-test' ) );
	}

	public function test_it_hooks_admin_init_and_init_late(): void {
		$hooks = $this->hooks();
		$hooks->register();

		self::assertSame( 10, has_action( 'admin_init', array( $hooks, 'upgrade' ) ) );
		self::assertSame( 20, has_action( 'init', array( $hooks, 'watch_version' ) ) );
	}

	public function test_an_admin_page_runs_the_pending_steps(): void {
		Functions\when( 'wp_doing_ajax' )->justReturn( false );
		Functions\when( 'wp_doing_cron' )->justReturn( false );

		$this->hooks()->upgrade();

		self::assertSame( 1, $this->runs );
	}

	public function test_ajax_and_cron_requests_do_not(): void {
		Functions\when( 'wp_doing_ajax' )->justReturn( true );
		Functions\when( 'wp_doing_cron' )->justReturn( false );
		$this->hooks()->upgrade();

		Functions\when( 'wp_doing_ajax' )->justReturn( false );
		Functions\when( 'wp_doing_cron' )->justReturn( true );
		$this->hooks()->upgrade();

		self::assertSame( 0, $this->runs );
	}

	public function test_a_failed_step_is_shown_to_administrators_and_the_page_still_loads(): void {
		Functions\when( 'wp_doing_ajax' )->justReturn( false );
		Functions\when( 'wp_doing_cron' )->justReturn( false );
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( '__' )->returnArg();
		Functions\when( 'esc_html' )->returnArg();
		$this->fails = new StepFailed( 2, new \RuntimeException( 'Table full' ) );
		$hooks       = $this->hooks();

		$hooks->upgrade();
		self::assertSame( 10, has_action( 'admin_notices', array( $hooks, 'failure_notice' ) ) );

		ob_start();
		$hooks->failure_notice();
		$notice = (string) ob_get_clean();

		self::assertStringContainsString( 'notice-error', $notice );
		self::assertStringContainsString( 'step 2', $notice );
		self::assertStringContainsString( 'Table full', $notice );
	}

	public function test_a_new_plugin_version_flushes_rewrite_rules_once_and_is_stored(): void {
		$this->stored_version = '0.0.0-old';
		$this->hooks()->watch_version();
		self::assertSame( array( 'flush', 'version 0.0.0-test' ), $this->state );

		$this->state          = array();
		$this->stored_version = '0.0.0-test';
		$this->hooks()->watch_version();
		self::assertSame( array(), $this->state );
	}
}
