<?php
/**
 * The WP-Cron events that make products for detected affiliate links.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Cron;

use Affibloom\Application\Monetize\AffiliateDetector;
use Affibloom\Application\Upgrade\UpgradeLock;
use Affibloom\Domain\Settings\SettingsRepository;
use Affibloom\Interface\Hooks\AutoDetectHooks;
use Affibloom\Module;

/**
 * F35 R13, D5, D23 (Task C2C): `affibloom_auto_detect`, the single run a detection schedules 5 seconds later, and
 * `affibloom_auto_detect_daily`, Lasso's daily job, scheduled on `init` while "Auto-Detect Affiliate Links" is on and
 * cleared while it is off. Each run makes products for up to 100 links still without one, behind a lock so two runs
 * never make the same product, and stops as soon as the setting is switched off.
 */
final class AutoDetectCron implements Module {

	/** Links a run takes (Lasso's batch). */
	public const BATCH = 100;

	/**
	 * Every hook this module schedules, for the CronRegistry deactivation clears.
	 *
	 * @var list<string>
	 */
	public const HOOKS = array( AutoDetectHooks::EVENT, AutoDetectHooks::DAILY );

	/**
	 * Builds the module.
	 *
	 * @param AffiliateDetector  $detector The detector, which makes the products.
	 * @param UpgradeLock        $lock     One run at a time.
	 * @param SettingsRepository $settings The site's settings.
	 */
	public function __construct( private AffiliateDetector $detector, private UpgradeLock $lock, private SettingsRepository $settings ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( AutoDetectHooks::EVENT, array( $this, 'run' ) );
		add_action( AutoDetectHooks::DAILY, array( $this, 'run' ) );
		add_action( 'init', array( $this, 'schedule_daily' ) );
	}

	/**
	 * Makes the products, one run at a time.
	 */
	public function run(): void {
		if ( ! $this->on() || ! $this->lock->acquire() ) {
			return;
		}
		try {
			$this->detector->create_products( self::BATCH, fn(): bool => $this->on() );
		} finally {
			$this->lock->release();
		}
	}

	/**
	 * The daily run while the setting is on; none while it is off.
	 */
	public function schedule_daily(): void {
		$next = wp_next_scheduled( AutoDetectHooks::DAILY );
		if ( ! $this->on() ) {
			if ( false !== $next ) {
				wp_clear_scheduled_hook( AutoDetectHooks::DAILY );
			}
			return;
		}
		if ( false === $next ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', AutoDetectHooks::DAILY );
		}
	}

	/**
	 * Whether "Auto-Detect Affiliate Links" is on, read afresh.
	 */
	private function on(): bool {
		return $this->settings->get()->bool( 'auto_detect_links' );
	}
}
