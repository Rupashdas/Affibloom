<?php
/**
 * Whether a grid prints its disclosure.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Display;

/**
 * The grid's one disclosure after its cards (F30 R17, D5): classic settings show it when `disclosure="enable"` or
 * Disclosure in Grids is on (`disable` is not read); customised settings let `enable` and `disable` win over the
 * setting. It needs the site's disclosure text, and shows for an empty group too.
 */
final class GridDisclosure {

	/**
	 * Whether the grid shows its disclosure.
	 *
	 * @param bool   $customised Whether the Display settings are customised (advanced rendering).
	 * @param string $attribute  The shortcode's `disclosure`: `enable`, `disable` or anything else.
	 * @param bool   $setting    Disclosure in Grids (`show_disclosure_grid`).
	 */
	public static function shows( bool $customised, string $attribute, bool $setting ): bool {
		if ( 'enable' === $attribute ) {
			return true;
		}
		if ( $customised && 'disable' === $attribute ) {
			return false;
		}

		return $setting;
	}
}
