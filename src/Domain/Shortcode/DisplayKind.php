<?php
/**
 * What a shortcode draws.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Shortcode;

/**
 * The display a shortcode asks for (F31 A8 `type`). Task 18S1 draws the single box; 18S4-18S7 and the DT tasks add the
 * other kinds.
 */
enum DisplayKind: string {

	case Single = 'single';
}
