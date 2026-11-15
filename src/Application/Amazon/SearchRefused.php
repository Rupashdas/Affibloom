<?php
/**
 * An Amazon search or selection that could not be done.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Amazon;

use RuntimeException;

/**
 * An Amazon search or selection that could not be done (C1M), and why: no credentials, Amazon unavailable (Lasso's
 * HttpError and AuthFailed, R68, and a throttled request), Amazon's error answer, or an item the person was not shown.
 */
final class SearchRefused extends RuntimeException {

	public const NOT_CONFIGURED = 'not_configured';
	public const UNAVAILABLE    = 'unavailable';
	public const FAILED         = 'failed';
	public const INVALID        = 'invalid_selection';

	/**
	 * Builds the refusal.
	 *
	 * @param string $reason One of the constants.
	 */
	public function __construct( public readonly string $reason ) {
		parent::__construct( "Amazon search refused: {$reason}" );
	}
}
