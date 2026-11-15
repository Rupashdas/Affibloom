<?php
/**
 * A bulk Amazon sync that cannot start.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Amazon;

use RuntimeException;

/**
 * A bulk Amazon sync that cannot start (Lasso's lasso_bulk_sync_amazon_start answers, R42): one is running, nothing
 * is pending, or Amazon cannot be asked (D12: no credentials).
 */
final class BulkSyncRefused extends RuntimeException {

	public const RUNNING        = 'running';
	public const NOTHING        = 'nothing';
	public const NOT_CONFIGURED = 'not_configured';

	/**
	 * Builds the refusal.
	 *
	 * @param string $reason One of the constants.
	 */
	public function __construct( public readonly string $reason ) {
		parent::__construct( 'Bulk Amazon sync refused: ' . $reason ); // For logs; the words are the Interface's.
	}
}
