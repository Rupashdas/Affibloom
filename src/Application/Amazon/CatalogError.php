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
 * credentials), HttpError (the service did not answer; for a token, also 5xx or 429), ApiError (the catalogue answered
 * with an error, Amazon's message kept) or NotFound (no such item).
 */
final class CatalogError extends RuntimeException {

	public const AUTH_FAILED = 'AuthFailed';
	public const HTTP_ERROR  = 'HttpError';
	public const API_ERROR   = 'ApiError';
	public const NOT_FOUND   = 'NotFound';

	/**
	 * Builds the error.
	 *
	 * @param string $kind   One of the constants.
	 * @param string $detail What happened (Lasso's message, or Amazon's): for logs and the command line, never a page.
	 * @param string $reason For a refused token, Amazon's HTTP status and error code ("HTTP 401, invalid_client"),
	 *                       which the card shows (C1Q0); else ''.
	 */
	public function __construct( public readonly string $kind, string $detail, public readonly string $reason = '' ) {
		parent::__construct( $detail );
	}

	/**
	 * Whether the service was unavailable (HttpError) rather than refusing (R21): stored data is then kept.
	 */
	public function unavailable(): bool {
		return self::HTTP_ERROR === $this->kind;
	}
}
