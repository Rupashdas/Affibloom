<?php
/**
 * A button or badge text as an icon and its words (F28).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Icons;

/**
 * A button or badge text as an icon and its words (F28 requirements B5). The stored form is the site's own:
 * `<span class="<class>"></span> <words>`, or the words alone. Only a text of exactly that shape is split; any other
 * HTML stays the plain field's business.
 */
final class IconText {

	/** One leading empty span with only a class, an optional space, then text with no HTML. */
	private const SHAPE = '/^<span class="([^"<>]+)"><\/span> ?([^<>]*)$/';

	/**
	 * Builds a text; use split().
	 *
	 * @param string $css_class The span's class, as written (it may name no icon in the set); '' for none.
	 * @param string $words     The words after it.
	 */
	private function __construct( public readonly string $css_class, public readonly string $words ) {}

	/**
	 * The text's icon and words, or null when it holds other HTML.
	 *
	 * @param string $value A stored text.
	 */
	public static function split( string $value ): ?self {
		if ( ! str_contains( $value, '<' ) && ! str_contains( $value, '>' ) ) {
			return new self( '', $value );
		}
		if ( 1 !== preg_match( self::SHAPE, $value, $match ) ) {
			return null;
		}

		return new self( $match[1], $match[2] );
	}

	/**
	 * The same words with another icon ('' for none).
	 *
	 * @param string $css_class The icon's class.
	 */
	public function with_icon( string $css_class ): self {
		return new self( $css_class, $this->words );
	}

	/**
	 * The text as stored: the span, a space when there are words, the words.
	 */
	public function compose(): string {
		if ( '' === $this->css_class ) {
			return $this->words;
		}

		return '<span class="' . $this->css_class . '"></span>' . ( '' === $this->words ? '' : ' ' . $this->words );
	}
}
