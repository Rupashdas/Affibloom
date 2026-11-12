<?php
/**
 * The bulk add run in one option.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Domain\Product\BulkRun;
use Affibloom\Domain\Product\BulkRunRepository;

/**
 * The bulk add run in one option, not autoloaded (it is read only by the dialog and the background job).
 */
final class OptionBulkRunRepository implements BulkRunRepository {

	public const OPTION = 'affibloom_bulk_add';

	/**
	 * The stored run, or null.
	 */
	public function get(): ?BulkRun {
		return BulkRun::from_array( get_option( self::OPTION, null ) );
	}

	/**
	 * Replaces the stored run.
	 *
	 * @param BulkRun $run The run.
	 */
	public function save( BulkRun $run ): void {
		update_option( self::OPTION, $run->to_array(), false );
	}
}
