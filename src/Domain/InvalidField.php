<?php
/**
 * A field value the rules do not accept. "reason" is a code; the Interface layer words it.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain;

use DomainException;

/**
 * A field value the rules do not accept. "reason" is a code; the Interface layer words it.
 */
final class InvalidField extends DomainException {

	/**
	 * Builds the error.
	 *
	 * @param string $field  Field name, e.g. "url".
	 * @param string $reason Reason code, e.g. "invalid_url".
	 */
	public function __construct( public readonly string $field, public readonly string $reason ) {
		parent::__construct( $field . ': ' . $reason );
	}
}
