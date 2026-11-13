<?php
/**
 * A repoint that no longer fits; nothing was changed.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Products;

use RuntimeException;

/**
 * Lasso_Repoint_Merge::merge()'s refusals (F1 requirements 9 S10), as reason codes Interface\Messages words:
 * `repoint_ids`, `repoint_source`, `repoint_target`, `repoint_url`, `repoint_mismatch`.
 */
final class RepointRefused extends RuntimeException {

	/**
	 * Builds the error.
	 *
	 * @param string $reason The reason code.
	 */
	public function __construct( public readonly string $reason ) {
		parent::__construct( 'Repoint refused: ' . $reason );
	}
}
