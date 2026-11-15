<?php
/**
 * How syncing one product went.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Amazon;

use Affibloom\Domain\Amazon\BulkSyncRun;

/**
 * How syncing one product went (Lasso's sync_amazon_metadata_for_post, C1 R44): succeeded, skipped or failed, and why,
 * as a reason the Interface puts into Lasso's words.
 */
final class SyncOutcome {

	public const INVALID      = 'invalid';
	public const NOT_AMAZON   = 'not_amazon';
	public const SEARCH_TITLE = 'search_title';
	public const UNRESOLVED   = 'unresolved';
	public const PRESENT      = 'present';
	public const NOT_FOUND    = 'not_found';
	public const FAILED       = 'failed';
	public const INCOMPLETE   = 'incomplete';
	public const UNVERIFIED   = 'unverified';
	public const SYNCED       = 'synced';

	/**
	 * Use the named constructors.
	 *
	 * @param string $kind   BulkSyncRun::SUCCEEDED, SKIPPED or FAILED.
	 * @param string $reason One of the constants.
	 */
	private function __construct( public readonly string $kind, public readonly string $reason ) {}

	/**
	 * It worked.
	 *
	 * @param string $reason SEARCH_TITLE, PRESENT or SYNCED.
	 */
	public static function succeeded( string $reason ): self {
		return new self( BulkSyncRun::SUCCEEDED, $reason );
	}

	/**
	 * Not an Amazon product: nothing to do (Lasso counts it skipped).
	 */
	public static function skipped(): self {
		return new self( BulkSyncRun::SKIPPED, self::NOT_AMAZON );
	}

	/**
	 * It did not work.
	 *
	 * @param string $reason Why.
	 */
	public static function failed( string $reason ): self {
		return new self( BulkSyncRun::FAILED, $reason );
	}
}
