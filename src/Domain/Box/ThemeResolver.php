<?php
/**
 * Which theme a box draws: the display type's, the product's, or the box's own.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Box;

use Affibloom\Domain\Display\Theme;

/**
 * Lasso's theme order for a Single box (F5 R3; single.php 19-59): the display type's theme, then the product's
 * Display Theme when it has one, then the box's own `theme` when it is not empty. Names compare without case; an
 * unknown one draws Cactus (Theme::from(), as Lasso's normalize_theme_name_string()).
 */
final class ThemeResolver {

	/**
	 * The theme the box draws.
	 *
	 * @param Theme      $type    The display type's theme (Settings → Display).
	 * @param Theme|null $product The product's Display Theme; null for "Default", the type's.
	 * @param string     $box     The box's own `theme`; '' when it names none (the block's attribute is Task 20B's).
	 */
	public static function resolve( Theme $type, ?Theme $product, string $box ): Theme {
		if ( '' !== trim( $box ) ) {
			return Theme::from( $box );
		}

		return $product ?? $type;
	}
}
