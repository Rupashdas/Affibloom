<?php
/**
 * How many cards a grid row holds.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Display;

/**
 * A grid's column count (F30 R14, N1): the shortcode's `columns` when it is 1-5, else the site's count (1-3) with
 * customised settings and 2 with classic ones. Lasso's customised grid ignores `columns` (its CSS reads the site's
 * count); Affibloom's own shortcode honours it in both modes (N1, decided 2026-11-14); `[lasso]` keeps Lasso's rule
 * (Task 27).
 */
final class GridColumns {

	/**
	 * Classic settings' count when the shortcode gives none.
	 */
	public const CLASSIC_DEFAULT = 2;

	/**
	 * The count.
	 *
	 * @param int|null $attribute  The shortcode's `columns` (1-5); null none.
	 * @param bool     $customised Whether the Display settings are customised.
	 * @param int      $site       The site's column count (Display settings, 1-3).
	 */
	public static function of( ?int $attribute, bool $customised, int $site ): int {
		if ( null !== $attribute ) {
			return $attribute;
		}

		return $customised ? $site : self::CLASSIC_DEFAULT;
	}
}
