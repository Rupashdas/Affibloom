<?php
/**
 * One button in a box.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Box;

/**
 * One button in a box (F5 R37): its class, its link attributes in Lasso's order, and its text, which may hold the
 * site's icon span (the template prints it through the icon allowlist).
 */
final class BoxButton {

	/**
	 * Builds a button.
	 *
	 * @param string                $css_class  `affibloom-button-1` for the first, `affibloom-button-2` for the others.
	 * @param array<string, string> $attributes Its link attributes (LinkAttributes), raw.
	 * @param string                $text       Its text, raw.
	 */
	public function __construct(
		public readonly string $css_class,
		public readonly array $attributes,
		public readonly string $text,
	) {}
}
