<?php
/**
 * What the Product Data API card says about the stored credentials.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Amazon;

/**
 * What the Product Data API card says about the stored credentials (C1 D4): not all four fields are set; they are set
 * but Validate (or Save Changes) has not confirmed them; or Amazon granted a token for exactly these values;
 * or Amazon refused them (C1Q0: kept, marked refused).
 */
enum CredentialsState: string {

	case NotSetUp  = 'not-set-up';
	case Saved     = 'saved';
	case Validated = 'validated';
	case Refused   = 'refused';
}
