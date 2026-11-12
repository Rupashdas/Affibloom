<?php
/**
 * The WP-Cron event that adds a bulk run's links.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Cron;

use Affibloom\Application\Products\BulkAddService;
use Affibloom\Module;

/**
 * The WP-Cron event that adds a bulk run's links.
 */
final class BulkAddCron implements Module {

	public const HOOK = 'affibloom_bulk_add';

	/**
	 * Builds the module.
	 *
	 * @param BulkAddService $bulk Bulk add.
	 */
	public function __construct( private BulkAddService $bulk ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( self::HOOK, array( $this->bulk, 'process' ) );
	}
}
