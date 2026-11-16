<?php
/**
 * The shortcodes the admin screens copy.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface;

/**
 * The copied shortcode strings (F31 D9): the product's ID alone addresses it (Lasso's `ref`/`rel` were fallbacks for
 * its own scans).
 */
final class ShortcodeText {

	/**
	 * A product's box, `[affibloom id="<id>"]`; '' for an unsaved product (Lasso's button is then disabled, R30).
	 *
	 * @param int $id The product's ID.
	 */
	public static function product( int $id ): string {
		return $id > 0 ? '[affibloom id="' . $id . '"]' : '';
	}

	/**
	 * A group's grid, `[affibloom type="grid" group="<slug>"]` (Lasso's `[lasso type="grid" category="<slug>"]`, R32);
	 * '' for a group without a slug.
	 *
	 * @param string $slug The group's slug.
	 */
	public static function group( string $slug ): string {
		return '' === $slug ? '' : '[affibloom type="grid" group="' . $slug . '"]';
	}
}
