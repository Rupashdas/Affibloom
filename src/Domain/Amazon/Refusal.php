<?php
/**
 * Amazon refused the stored credentials.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Amazon;

/**
 * Amazon refused the stored credentials (C1Q0, deviation 119): when and why, as Amazon said it ("HTTP 401,
 * invalid_client"). Lasso clears refused credentials (R22); Affibloom keeps them with this mark, so nothing is lost and
 * the card can say what happened. Nothing asks Amazon with refused credentials until they are validated again.
 */
final class Refusal {

	/**
	 * Builds the mark.
	 *
	 * @param string $reason Amazon's answer: the HTTP status and its error code, or the client's message.
	 * @param string $at     When, "Y-m-d H:i:s" in UTC.
	 */
	public function __construct( public readonly string $reason, public readonly string $at ) {}
}
