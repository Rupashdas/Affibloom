<?php
/**
 * The WP-Cron event that runs a bulk Amazon sync.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Cron;

use Affibloom\Application\Amazon\BulkSyncService;
use Affibloom\Module;

/**
 * The WP-Cron event that runs a bulk Amazon sync's passes (C1 D12; Lasso's lasso_bulk_sync_amazon queue).
 */
final class AmazonBulkSyncCron implements Module {

	public const HOOK = 'affibloom_amazon_bulk_sync';

	/**
	 * Every hook this module schedules, for the CronRegistry deactivation clears.
	 *
	 * @var list<string>
	 */
	public const HOOKS = array( self::HOOK );

	/**
	 * Builds the module.
	 *
	 * @param BulkSyncService $sync Bulk Amazon sync.
	 */
	public function __construct( private BulkSyncService $sync ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( self::HOOK, array( $this->sync, 'process' ) );
	}
}
