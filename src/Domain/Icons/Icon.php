<?php
/**
 * One button icon: a class a text's `<span class="…"></span>` names, and the image it shows (F28).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Icons;

use Affibloom\Domain\InvalidField;

/**
 * One button icon: a class a text's `<span class="…"></span>` names, and the image it shows (F28 requirements B1-B2).
 * "Imported" icons are drawn by the site's own CSS; the others by the CSS Affibloom prints for them.
 */
final class Icon {

	/** A class such as "full-review-icon": lower-case words joined by dashes, ending in "-icon" (the site's names). */
	public const CLASS_RULE = '/^[a-z0-9]+(?:-[a-z0-9]+)*-icon$/';

	/** An image link that cannot leave the url("…") it is printed in: no quote, bracket, backslash, angle or space. */
	private const URL_RULE = '/^[^\s"\'()\\\\<>]+$/';

	/** A name is at most this long. */
	public const NAME_MAX = 40;

	/**
	 * Builds an icon; use imported() or IconSet::add().
	 *
	 * @param string $css_class      The class, e.g. "amazon-icon".
	 * @param string $name       What the editor sees, 1-40 characters.
	 * @param string $url        The image, as the CSS refers to it.
	 * @param int    $attachment The media library item, or 0 when it is not one.
	 * @param bool   $imported   Drawn by the site's own CSS (true), or by Affibloom's.
	 * @throws InvalidField When a value breaks its rule.
	 */
	public function __construct(
		public readonly string $css_class,
		public readonly string $name,
		public readonly string $url,
		public readonly int $attachment,
		public readonly bool $imported,
	) {
		if ( 1 !== preg_match( self::CLASS_RULE, $css_class ) ) {
			throw new InvalidField( 'class', 'invalid_class' );
		}
		$length = mb_strlen( $name );
		if ( '' === trim( $name ) || $length > self::NAME_MAX ) {
			throw new InvalidField( 'name', 'invalid_name' );
		}
		if ( 1 !== preg_match( self::URL_RULE, $url ) ) {
			throw new InvalidField( 'image', 'invalid_image' );
		}
	}

	/**
	 * An icon the site's CSS defines, named after its class ("full-review-icon" → "Full review").
	 *
	 * @param string $css_class      The class.
	 * @param string $url        The image, as the CSS writes it.
	 * @param int    $attachment The media library item, or 0.
	 * @throws InvalidField When the class or the link breaks its rule.
	 */
	public static function imported( string $css_class, string $url, int $attachment ): self {
		$words = str_replace( '-', ' ', substr( $css_class, 0, -strlen( '-icon' ) ) );

		return new self( $css_class, ucfirst( $words ), $url, $attachment, true );
	}
}
