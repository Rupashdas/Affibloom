<?php
/**
 * A link's attributes as a reader sees them.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\LinkIndex;

/**
 * What Lasso's scan stored in a post (F21 R38, R39), printed at render time instead (D13, D14): nothing is written.
 *
 * - A link the index ties to a product takes the product's public link (R40), except an Amazon sponsored URL and the
 *   public link with a query string of its own (pass-through); `target="_blank"` or none (New Window); `rel` replaced
 *   by nofollow (NoFollow), sponsored (Sponsored) and noopener (New Window); and Affibloom's click attributes,
 *   `data-affibloom-id` and `data-affibloom-location` (deviation 146).
 * - Any other link: a `_blank` one gets noopener and loses noreferrer; a lone `1` is dropped from `rel` (Lasso's
 *   leftover); an empty `rel` goes.
 *
 * The answer maps each attribute to its new value, null removing it; attributes not named stay as they are.
 */
final class RenderedAttributes {

	/**
	 * A tied link's attributes.
	 *
	 * @param string $href        The link's href as stored.
	 * @param string $public_link The product's public link.
	 * @param bool   $new_tab     The product's New Window.
	 * @param bool   $nofollow    The product's NoFollow.
	 * @param bool   $sponsored   The product's Sponsored.
	 * @param int    $product_id  The product.
	 * @param int    $location_id The link's row in the index.
	 * @return array<string, string|null>
	 */
	public static function tied( string $href, string $public_link, bool $new_tab, bool $nofollow, bool $sponsored, int $product_id, int $location_id ): array {
		$rel = array_values( array_filter( array( $nofollow ? 'nofollow' : '', $sponsored ? 'sponsored' : '', $new_tab ? 'noopener' : '' ) ) );

		return array(
			'href'                    => self::keeps_href( $href, $public_link ) ? $href : $public_link,
			'target'                  => $new_tab ? '_blank' : null,
			'rel'                     => array() === $rel ? null : implode( ' ', $rel ),
			'data-affibloom-id'       => (string) $product_id,
			'data-affibloom-location' => (string) $location_id,
		);
	}

	/**
	 * Any other link's attributes (R38's noopener for every `_blank` link).
	 *
	 * @param string $target The link's target.
	 * @param string $rel    The link's rel.
	 * @return array<string, string|null>
	 */
	public static function untied( string $target, string $rel ): array {
		$split  = preg_split( '/\s+/', trim( $rel ) );
		$tokens = array_values( array_diff( false === $split ? array() : $split, array( '', '1' ) ) );
		if ( '_blank' === $target ) {
			$tokens   = array_values( array_diff( $tokens, array( 'noreferrer' ) ) );
			$tokens[] = 'noopener';
		}
		$tokens = array_values( array_unique( $tokens ) );
		$joined = implode( ' ', $tokens );
		if ( trim( $rel ) === $joined ) {
			return array();
		}

		return array( 'rel' => '' === $joined ? null : $joined );
	}

	/**
	 * Whether the stored href stays (R39): empty public link, an Amazon sponsored click URL, or the public link itself
	 * with a query string (a cloaked link's pass-through parameters).
	 *
	 * @param string $href        The stored href.
	 * @param string $public_link The product's public link.
	 */
	private static function keeps_href( string $href, string $public_link ): bool {
		if ( '' === $public_link || 1 === preg_match( '#^https?://(?:www\.)?amazon\.[a-z.]+/sspa/#i', $href ) ) {
			return true;
		}
		$query = strpos( $href, '?' );

		return false !== $query && rtrim( substr( $href, 0, $query ), '/' ) === rtrim( $public_link, '/' );
	}
}
