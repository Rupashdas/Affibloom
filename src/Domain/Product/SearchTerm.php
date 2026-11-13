<?php
/**
 * What an editor typed in the Dashboard's search box, cleaned as Lasso cleans it.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Product;

/**
 * What an editor typed in the Dashboard's search box, cleaned as Lasso cleans it, and the shapes that mean
 * more than their text.
 *
 * Lasso's rules, copied exactly (F19 requirements, section 3):
 * - the box's focusout replaces every character that is not `a-zA-Z0-9+-.#` or CJK with a space, then trims
 *   (`admin/assets/js/view-js.php`: `value.replace( /[^a-zA-Z0-9\+\-\.\#一-鿿]/g, ' ' ).trim()`);
 * - the handler then turns each space into `%` and wraps the result in `%…%`
 *   (`classes/class-lasso-db.php::build_dashboard_search_term_string`), so a pasted link still finds the
 *   product it belongs to;
 * - an all-digits term is also a record ID, and a term shaped like an ASIN is also an exact ASIN.
 *
 * Runs of removed characters are collapsed to one space here. Lasso leaves them, which gives `a%%%b`; in a
 * LIKE that matches exactly what `a%b` matches, so only the text put back in the box differs.
 */
final class SearchTerm {

	/** Everything Lasso keeps: letters, digits, + - . # and CJK. Anything else becomes a space. */
	private const KEEP = '/[^A-Za-z0-9+\-.#\x{4e00}-\x{9fff}]+/u';

	/** Lasso's ASIN shape for the exact-match arm, matched without case. */
	private const ASIN = '/^B[A-Z0-9]{8,}$/i';

	/**
	 * Use from_input().
	 *
	 * @param string $text The cleaned text.
	 */
	private function __construct( private readonly string $text ) {}

	/**
	 * Cleans what was typed.
	 *
	 * @param string $input The raw text from the search box.
	 */
	public static function from_input( string $input ): self {
		$cleaned = preg_replace( self::KEEP, ' ', $input );

		return new self( trim( is_string( $cleaned ) ? $cleaned : '' ) );
	}

	/**
	 * The cleaned text: what goes back in the box and into the URL.
	 */
	public function text(): string {
		return $this->text;
	}

	/**
	 * Whether there is nothing to search for.
	 */
	public function is_empty(): bool {
		return '' === $this->text;
	}

	/**
	 * The term as one LIKE pattern, with its spaces as wildcards: "Wave Momentum" becomes
	 * "%Wave%Momentum%". Each word is escaped first, so a % or _ an editor types is that character and not a
	 * wildcard; Lasso escapes neither.
	 *
	 * @param callable(string): string $escape Escapes a LIKE value; the repository passes $wpdb->esc_like().
	 */
	public function like( callable $escape ): string {
		$words = array_map( $escape, explode( ' ', $this->text ) );

		return '%' . implode( '%', $words ) . '%';
	}

	/**
	 * The product ID this term also is, or null: Lasso matches `p.ID = %d` for an all-digits term.
	 */
	public function post_id(): ?int {
		if ( '' === $this->text || ! ctype_digit( $this->text ) ) {
			return null;
		}

		$id = (int) $this->text;

		return $id > 0 ? $id : null;
	}

	/**
	 * The ASIN this term also is, upper-cased, or null: Lasso adds an exact `product_id` match for a term
	 * shaped like one.
	 */
	public function asin(): ?string {
		return 1 === preg_match( self::ASIN, $this->text ) ? strtoupper( $this->text ) : null;
	}
}
