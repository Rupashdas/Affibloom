<?php
/**
 * The hourly WP-Cron event of the scheduled Amazon refresh, and its passes.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Cron;

use Affibloom\Application\Amazon\PriceRefreshService;
use Affibloom\Module;

/**
 * The scheduled Amazon refresh's events (C1Q4b; Lasso's lasso_cron_update_amazon, R37): the hourly event, scheduled
 * only while "Update Amazon pricing daily" is on and cleared when it is off, and the background passes of a run.
 */
final class AmazonRefreshCron implements Module {

	public const HOOK = 'affibloom_amazon_refresh';

	public const PASS = 'affibloom_amazon_refresh_pass';

	/**
	 * Every hook this module schedules, for the CronRegistry deactivation clears.
	 *
	 * @var list<string>
	 */
	public const HOOKS = array( self::HOOK, self::PASS );

	/**
	 * Builds the module.
	 *
	 * @param PriceRefreshService $refresh The scheduled refresh.
	 */
	public function __construct( private PriceRefreshService $refresh ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( self::HOOK, array( $this->refresh, 'tick' ) );
		add_action( self::PASS, array( $this->refresh, 'process' ) );
		add_action( 'init', array( $this, 'schedule' ) );
	}

	/**
	 * Keeps the hourly event in step with "Update Amazon pricing daily".
	 */
	public function schedule(): void {
		$scheduled = false !== wp_next_scheduled( self::HOOK );
		if ( $this->refresh->enabled() && ! $scheduled ) {
			wp_schedule_event( time(), 'hourly', self::HOOK );
		} elseif ( ! $this->refresh->enabled() && $scheduled ) {
			wp_clear_scheduled_hook( self::HOOK );
		}
	}
}
