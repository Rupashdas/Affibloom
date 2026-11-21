<?php
/**
 * The daily removal of old click rows.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Cron;

use Affibloom\Application\Clicks\ClickRetention;
use Affibloom\Module;

/**
 * Rows older than 400 days go with the daily job (F12 AC8, ADR-005; Task 33C): `affibloom_daily`, which link health
 * schedules (LinkCheckCron, Phase 6), so no event of its own is added.
 */
final class ClickRetentionCron implements Module {

	/**
	 * Builds the hook.
	 *
	 * @param ClickRetention $retention The retention.
	 */
	public function __construct( private ClickRetention $retention ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( LinkCheckCron::DAILY, array( $this, 'run' ) );
	}

	/**
	 * Removes the old rows.
	 */
	public function run(): void {
		$this->retention->run( time() );
	}
}
