<?php
/**
 * The list element and class a list display's `bullets` asks for.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Display;

/**
 * A list's bullets (F30 R21, R24): no or an unknown `bullets` is Lasso's numbered list (`<ol class="affibloom-list-ol
 * affibloom-list-style-decimal">`); `decimal`, `alpha` and `roman` an `<ol class="affibloom-list …">`; `square`,
 * `circle` and `hide` a `<ul class="affibloom-list …">`. Lasso compares the value as written, so `Roman` is unknown.
 */
final class ListBullets {

	/** The numbered styles, which draw an `ol`. */
	public const ORDERED = array( 'decimal', 'alpha', 'roman' );

	/** The unnumbered styles, which draw a `ul`; `hide` draws no marks. */
	public const UNORDERED = array( 'square', 'circle', 'hide' );

	/**
	 * Builds the bullets.
	 *
	 * @param string $tag     `ol` or `ul`.
	 * @param string $classes The list's classes.
	 */
	private function __construct( public readonly string $tag, public readonly string $classes ) {}

	/**
	 * The bullets for a `bullets` value.
	 *
	 * @param string $bullets The shortcode's `bullets`; '' none.
	 */
	public static function of( string $bullets ): self {
		if ( in_array( $bullets, self::ORDERED, true ) ) {
			return new self( 'ol', 'affibloom-list affibloom-list-style-' . $bullets );
		}
		if ( in_array( $bullets, self::UNORDERED, true ) ) {
			return new self( 'ul', 'affibloom-list affibloom-list-style-' . $bullets );
		}

		return new self( 'ol', 'affibloom-list-ol affibloom-list-style-decimal' );
	}
}
