<?php
/**
 * What a link points to.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\LinkIndex;

/**
 * Lasso's link types (F21 R25, D1) in Affibloom's words: a product of the library, a page of this site, or anything else.
 */
enum LinkType: string {
	case Product  = 'product';
	case Internal = 'internal';
	case External = 'external';
}
