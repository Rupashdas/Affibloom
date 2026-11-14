<?php
/**
 * Amazon did not give what was asked for.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Amazon;

use RuntimeException;

/**
 * Amazon did not give what was asked for, with Lasso's error code (C1 R13): AuthFailed (no token for these
 * credentials) or HttpError (the service did not answer, or answered 5xx or 429).
 */
final class CatalogError extends RuntimeException {

	public const AUTH_FAILED = 'AuthFailed';
	public const HTTP_ERROR  = 'HttpError';

	/**
	 * Builds the error.
	 *
	 * @param string $kind   One of the constants.
	 * @param string $detail What happened, for logs and the command line; never shown on a page.
	 */
	public function __construct( public readonly string $kind, string $detail ) {
		parent::__construct( $detail );
	}

	/**
	 * Whether the service was unavailable (HttpError) rather than refusing (R21): stored data is then kept.
	 */
	public function unavailable(): bool {
		return self::HTTP_ERROR === $this->kind;
	}
}
