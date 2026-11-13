<?php
/**
 * Finds the site's icon rules in CSS text (F28).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Icons;

/**
 * Finds the site's icon rules in CSS text: `span.<name>-icon { … content: url(<image>) … }`, the shape of the six
 * rules in the site's Additional CSS (F28 requirements section 2).
 */
final class CssIconRules {

	/**
	 * A selector that is exactly `span.<class>` (the start of the text or of a rule, after a "}"; a descendant such as
	 * `.x span.y-icon` draws the icon only inside .x, so it does not define it), then the rule's text up to its first
	 * "}" (the site nests @media blocks after the content line, which this does not need), and its content: url().
	 */
	private const RULE = '/(?:^|\})\s*span\.([a-z0-9]+(?:-[a-z0-9]+)*-icon)\s*\{[^}]*?content\s*:\s*url\(\s*[\'"]?([^\'")\s]+)[\'"]?\s*\)/';

	/**
	 * The icon rules in the CSS, in their order: [class, image as written]. A class defined twice counts once (the
	 * first); comments are ignored.
	 *
	 * @param string $css CSS text.
	 * @return list<array{string, string}>
	 */
	public static function parse( string $css ): array {
		$css = (string) preg_replace( '#/\*.*?\*/#s', ' ', $css );
		preg_match_all( self::RULE, $css, $matches, PREG_SET_ORDER );

		$found = array();
		foreach ( $matches as $match ) {
			if ( ! isset( $found[ $match[1] ] ) ) {
				$found[ $match[1] ] = array( $match[1], $match[2] );
			}
		}

		return array_values( $found );
	}
}
