<?php
/**
 * Whether a box prints the product's description, and the text it prints.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Box;

/**
 * Lasso's description rule (F5 R22; get_lasso_url 186-187, 324-327, 429 and Lasso_Post::is_show_description).
 *
 * Lasso writes `show_description = 1` while it renders a box whose flag is off but whose description has text (D2):
 * here the same answer is computed on every render and nothing is written when a page is viewed.
 */
final class DescriptionRule {

	/**
	 * Lasso's blank descriptions, compared exactly.
	 *
	 * @var list<string>
	 */
	private const BLANK = array( '', '<p><br></p>' );

	/**
	 * Whether the box prints the description block.
	 *
	 * @param bool|null $flag        The product's Show Description; null when never stored, which Lasso reads as on.
	 * @param string    $description The description as stored.
	 */
	public static function shows( ?bool $flag, string $description ): bool {
		// More than ten characters force it on (Lasso counts the stored HTML, not the visible text).
		if ( false !== $flag || strlen( $description ) > 10 ) {
			return true;
		}

		$spaced = (string) preg_replace( '/ +/', ' ', $description );

		return ! in_array( $description, self::BLANK, true ) && 1 !== preg_match( '/<p>\s<\/p>/', $spaced );
	}

	/**
	 * The text the box prints: empty paragraphs removed, as Lasso does before it renders.
	 *
	 * @param string $description The description as stored.
	 */
	public static function text( string $description ): string {
		return str_replace( '<p></p>', '', $description );
	}
}
