<?php
/**
 * One or more fields were refused; nothing was saved.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Products;

use RuntimeException;

/**
 * One or more fields were refused; nothing was saved.
 */
final class ValidationFailed extends RuntimeException {

	/**
	 * Builds the error.
	 *
	 * @param array<string, string> $errors Field => reason code (Interface\Messages has the words).
	 */
	public function __construct( public readonly array $errors ) {
		parent::__construct( 'Invalid fields: ' . implode( ', ', array_keys( $errors ) ) );
	}
}
