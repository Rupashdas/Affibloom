<?php
/**
 * Which of Lasso's three box layouts a theme draws.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Box;

use Affibloom\Domain\Display\Theme;

/**
 * Lasso's three Single layouts (F5 R5; single.php 304-315). The value names the template that prints it.
 */
enum LayoutFamily: string {

	case TwoBox   = 'box-2';
	case ThreeBox = 'box-3';
	case SixBox   = 'box-6';

	/**
	 * Cutter, Flow, Geek and Splash draw 2 boxes; Lab and Llama 3; Cactus and Money 6.
	 *
	 * @param Theme $theme The box's theme.
	 */
	public static function of( Theme $theme ): self {
		return match ( $theme->name() ) {
			'Cutter', 'Flow', 'Geek', 'Splash' => self::TwoBox,
			'Lab', 'Llama'                     => self::ThreeBox,
			default                            => self::SixBox,
		};
	}
}
