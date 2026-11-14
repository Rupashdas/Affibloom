<?php
/**
 * Validate could not confirm the credentials.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Amazon;

use RuntimeException;

/**
 * Validate could not confirm the credentials (C1 R20-R22): fields are missing, Amazon refused them (the stored ones are
 * then cleared), or Amazon was unavailable (the stored ones are kept).
 */
final class CredentialsNotValidated extends RuntimeException {

	public const MISSING     = 'amazon_missing';
	public const REJECTED    = 'amazon_rejected';
	public const UNAVAILABLE = 'amazon_unavailable';

	/**
	 * Builds the refusal.
	 *
	 * @param string       $reason  One of the constants.
	 * @param list<string> $missing The empty fields, in Lasso's order (Credentials::FIELDS), for MISSING.
	 */
	public function __construct( public readonly string $reason, public readonly array $missing = array() ) {
		parent::__construct( $reason );
	}
}
