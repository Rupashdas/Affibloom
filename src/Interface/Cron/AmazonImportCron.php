<?php
/**
 * The WP-Cron event of the Amazon import.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Cron;

use Affibloom\Application\Monetize\AmazonImport;
use Affibloom\Application\Upgrade\UpgradeLock;
use Affibloom\Domain\Settings\SettingsRepository;
use Affibloom\Infrastructure\WordPress\AmazonImportSchedule;
use Affibloom\Infrastructure\WordPress\OptionSettingsRepository;
use Affibloom\Module;

/**
 * F35 R24, R25, D11, D23 (Task AM1): `affibloom_amazon_import` every five minutes while Auto-Monetization is on,
 * scheduled on `init` and cleared when the setting is switched off; each run makes products for up to 100 Amazon links
 * behind a lock, stops after the current link when the setting goes off, and keeps its batch's figures for the status
 * block (R29; `affibloom_amazon_import_batch`: total, completed, running).
 */
final class AmazonImportCron implements Module {

	/** Links a run takes (Lasso's batch). */
	public const BATCH = 100;

	/** The batch's figures. */
	public const STATE = 'affibloom_amazon_import_batch';

	/**
	 * Every hook this module schedules, for the CronRegistry deactivation clears.
	 *
	 * @var list<string>
	 */
	public const HOOKS = array( AmazonImportSchedule::HOOK );

	/**
	 * Builds the module.
	 *
	 * @param AmazonImport         $import   The import.
	 * @param AmazonImportSchedule $schedule Its event.
	 * @param UpgradeLock          $lock     One run at a time.
	 * @param SettingsRepository   $settings The site's settings.
	 */
	public function __construct( private AmazonImport $import, private AmazonImportSchedule $schedule, private UpgradeLock $lock, private SettingsRepository $settings ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_filter( 'cron_schedules', array( AmazonImportSchedule::class, 'interval' ) ); // phpcs:ignore WordPress.WP.CronInterval.ChangeDetected -- Lasso's five minutes (D11), set in AmazonImportSchedule::interval().
		add_action( AmazonImportSchedule::HOOK, array( $this, 'run' ) );
		add_action( 'init', array( $this, 'keep_scheduled' ) );
		add_action( 'update_option_' . OptionSettingsRepository::OPTION, array( $this, 'switched' ), 10, 2 );
	}

	/**
	 * One run, while the setting is on.
	 */
	public function run(): void {
		if ( ! $this->on() || ! $this->lock->acquire() ) {
			return;
		}
		update_option( self::STATE, array( 'running' => true ) + (array) get_option( self::STATE, array() ), false );
		try {
			$batch = $this->import->run( self::BATCH, fn(): bool => $this->on() );
		} finally {
			$this->lock->release();
		}
		update_option(
			self::STATE,
			array(
				'running'   => false,
				'total'     => $batch['queued'],
				'completed' => $batch['done'],
			),
			false
		);
	}

	/**
	 * The event while the setting is on; none while it is off.
	 */
	public function keep_scheduled(): void {
		if ( $this->on() ) {
			$this->schedule->start();
		} elseif ( false !== wp_next_scheduled( AmazonImportSchedule::HOOK ) ) {
			$this->schedule->stop();
		}
	}

	/**
	 * The settings were saved: Auto-Monetization switched off stops the import (D23).
	 *
	 * @param mixed $stored The option before.
	 * @param mixed $saved  The option now.
	 */
	public function switched( $stored, $saved ): void {
		unset( $stored );
		if ( is_array( $saved ) && array_key_exists( 'auto_monetize_amazon', $saved ) && ! $saved['auto_monetize_amazon'] ) {
			$this->schedule->stop();
		}
	}

	/**
	 * Whether Auto-Monetization is on, read afresh.
	 */
	private function on(): bool {
		return $this->settings->get()->bool( 'auto_monetize_amazon' );
	}
}
