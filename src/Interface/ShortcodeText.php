<?php
/**
 * The shortcodes and the block the admin screens copy.
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
	 * A product's block, as the editor saves a Single chosen in "Choose a Link" (F4; F1 R B9's Copy block, Task 24F):
	 * `<!-- wp:affibloom/product-box {"type":"single","productId":<id>} /-->`; '' for an unsaved product. Lasso's
	 * Copy block wrapped its shortcode in its block; Affibloom's block keeps the product's ID and nothing else.
	 *
	 * @param int $id The product's ID.
	 */
	public static function block( int $id ): string {
		return $id > 0 ? '<!-- wp:affibloom/product-box {"type":"single","productId":' . $id . '} /-->' : '';
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

	/**
	 * A comparison table, `[affibloom type="table" id="<id>"]` (Lasso's `[lasso type="table" id]`, F31 R33; Task
	 * 18S13); '' for a table not saved yet.
	 *
	 * @param int $id The table's ID.
	 */
	public static function table( int $id ): string {
		return $id > 0 ? '[affibloom type="table" id="' . $id . '"]' : '';
	}
}
