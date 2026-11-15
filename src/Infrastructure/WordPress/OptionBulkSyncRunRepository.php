<?php
/**
 * The bulk Amazon sync run in one option.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Domain\Amazon\BulkSyncRun;
use Affibloom\Domain\Amazon\BulkSyncRunRepository;

/**
 * The bulk Amazon sync run in one option, not autoloaded (read only by the banner, its routes and the job); Lasso's
 * five lasso_bulk_sync_amazon_* options in one.
 */
final class OptionBulkSyncRunRepository implements BulkSyncRunRepository {

	public const OPTION = 'affibloom_amazon_bulk_sync';

	/**
	 * The stored run, or null.
	 */
	public function get(): ?BulkSyncRun {
		return BulkSyncRun::from_array( get_option( self::OPTION, null ) );
	}

	/**
	 * Replaces the stored run.
	 *
	 * @param BulkSyncRun $run The run.
	 */
	public function save( BulkSyncRun $run ): void {
		update_option( self::OPTION, $run->to_array(), false );
	}
}
