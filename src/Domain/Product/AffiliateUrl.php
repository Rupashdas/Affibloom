<?php
/**
 * A product's link: an absolute http(s) address, kept exactly as pasted.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Product;

use Affibloom\Domain\InvalidField;

/**
 * A product's link: an absolute http(s) address, kept exactly as pasted (tracking parameters included).
 */
final class AffiliateUrl {

	/**
	 * Use from_string().
	 *
	 * @param string $value The link.
	 */
	private function __construct( private readonly string $value ) {}

	/**
	 * Accepts anything that is an http or https address with a host. Lasso refuses only an empty box; Affibloom also
	 * refuses what cannot be a link at all (F18, decision D1). Task 11B adds Lasso's repair of bare domains.
	 *
	 * @param string $raw   What was pasted.
	 * @param string $field Field name for the error.
	 * @throws InvalidField With reason "empty" or "invalid_url".
	 */
	public static function from_string( string $raw, string $field = 'url' ): self {
		$url = trim( $raw );
		if ( '' === $url ) {
			throw new InvalidField( $field, 'empty' );
		}

		$parts  = parse_url( $url ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Domain code does not call WordPress.
		$scheme = strtolower( (string) ( $parts['scheme'] ?? '' ) );
		$host   = (string) ( $parts['host'] ?? '' );
		if ( ! in_array( $scheme, array( 'http', 'https' ), true ) || '' === $host || preg_match( '/\s/', $host ) ) {
			throw new InvalidField( $field, 'invalid_url' );
		}

		return new self( $url );
	}

	/**
	 * The link, as pasted.
	 */
	public function value(): string {
		return $this->value;
	}

	/**
	 * The name Lasso gives a link before anything better is known (Lasso_Helper::get_title_by_url): the host's label
	 * before the suffix, hyphens as spaces, words capitalised. "mizuno.pxf.io" → "Pxf", "under-armour.com" →
	 * "Under Armour". Two improvements, neither changing an ordinary host: a two-letter country suffix after co, com,
	 * net, org, ac, gov or edu is skipped ("example.co.uk" → "Example", where Lasso says "Co"), and a single-label host
	 * is its own name (Lasso's would be empty, and a product needs a name).
	 */
	public function site_name(): string {
		$host = strtolower( (string) parse_url( $this->value, PHP_URL_HOST ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Domain code does not call WordPress.
		if ( str_starts_with( $host, 'www.' ) ) {
			$host = substr( $host, 4 );
		}

		$labels = explode( '.', $host );
		$count  = count( $labels );
		$name   = $count >= 2 ? $labels[ $count - 2 ] : $labels[0];
		if ( $count >= 3 && 2 === strlen( $labels[ $count - 1 ] ) && in_array( $name, array( 'co', 'com', 'net', 'org', 'ac', 'gov', 'edu' ), true ) ) {
			$name = $labels[ $count - 3 ];
		}

		return ucwords( str_replace( '-', ' ', $name ) );
	}
}
