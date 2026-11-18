<?php
/**
 * What a link points to.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\LinkIndex;

/**
 * Lasso's link types (F21 R25, D1) in Affibloom's words: a product of the library, a page of this site, anything else,
 * or SiteStripe markup.
 */
enum LinkType: string {
	case Product  = 'product';
	case Internal = 'internal';
	case External = 'external';

	/** Amazon's SiteStripe markup (R31). */
	case SiteStripe = 'sitestripe';

	/** A link in a WP Recipe Maker recipe a post shows (R32; Task 35E), Lasso's own type name. */
	case WpRecipeMaker = 'WpRecipeMaker';

	/** A link in a Mediavine Create card's description (R32; Task 35E), Lasso's own type name. */
	case MvCreate = 'MvCreate';

	/**
	 * The type a place gives its links whatever they point to: a recipe's (`wprm:<id>`), a Create card's (`mv:<id>`), or
	 * none.
	 *
	 * @param string $source The place in the host.
	 */
	public static function of_source( string $source ): ?self {
		return match ( true ) {
			str_starts_with( $source, 'wprm:' ) => self::WpRecipeMaker,
			str_starts_with( $source, 'mv:' )   => self::MvCreate,
			default                             => null,
		};
	}
}
