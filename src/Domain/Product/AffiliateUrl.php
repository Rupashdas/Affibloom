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
 * A product's link: an absolute http(s) address, kept as pasted after Lasso's repair (tracking parameters included).
 */
final class AffiliateUrl {

	/**
	 * Broken beginnings Lasso_Helper::add_https() turns into "https://", in its order (the replacements run one after
	 * another, so "https://%20https://x" ends as "https://x" through the last entry).
	 */
	private const BROKEN_PREFIXES = array(
		'https://%20https:/',
		'https://xhttps://',
		'http:/https://',
		'http://https://',
		'https://https://',
		'https://hhttps://',
		'https://]https://',
		'https://&quot;https://',
		'[gift_item link=&quot;https://',
		']https://',
		'https:///',
	);

	/**
	 * Use from_string().
	 *
	 * @param string $value The link.
	 */
	private function __construct( private readonly string $value ) {}

	/**
	 * Repairs the input as Lasso does, then accepts it if it is an http or https address with a host. Lasso refuses
	 * only an empty box; Affibloom also refuses what is still not a link after the repair (F18, decision D1).
	 *
	 * @param string $raw   What was pasted.
	 * @param string $field Field name for the error.
	 * @throws InvalidField With reason "empty" or "invalid_url".
	 */
	public static function from_string( string $raw, string $field = 'url' ): self {
		$url = self::repair( trim( $raw ) );
		if ( '' === $url ) {
			throw new InvalidField( $field, 'empty' );
		}

		$parts  = parse_url( $url ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Domain code does not call WordPress.
		$scheme = strtolower( (string) ( $parts['scheme'] ?? '' ) );
		$host   = (string) ( $parts['host'] ?? '' );
		// Lasso turns a host containing ".html" into a relative path ("/example.html"), which is not a link.
		if ( ! in_array( $scheme, array( 'http', 'https' ), true ) || '' === $host || preg_match( '/\s/', $host ) || str_contains( $host, '.html' ) ) {
			throw new InvalidField( $field, 'invalid_url' );
		}

		return new self( $url );
	}

	/**
	 * Lasso_Helper::add_https(): the broken prefixes become "https://"; a value with a dot and no scheme gets
	 * "https://" in front, after its leading slashes; e-mail addresses, mailto: links and anything starting with "["
	 * (a shortcode) are left alone. One improvement: Lasso looks only for a lower-case "http://" or "https://", so
	 * "HTTPS://x.com" or "ftp://x.com" would become "https://HTTPS://x.com" or "https://ftp://x.com"; here a value
	 * that already has a scheme, in any case, gets no prefix (and from_string() accepts only http and https).
	 *
	 * @param string $url Trimmed input.
	 */
	private static function repair( string $url ): string {
		$url = str_replace( self::BROKEN_PREFIXES, 'https://', $url );

		$left_alone = '' === $url
			|| str_starts_with( $url, '[' )
			|| str_contains( $url, 'mailto:' )
			|| false !== filter_var( $url, FILTER_VALIDATE_EMAIL );
		if ( $left_alone || preg_match( '#^[a-z][a-z0-9+.\-]*://#i', $url ) || ! str_contains( $url, '.' ) ) {
			return $url;
		}

		return 'https://' . ltrim( $url, '/' );
	}

	/**
	 * The key two links are the same product by: Lasso finds a product by its link ignoring case (MySQL's
	 * collation), a trailing slash, and "&amp;" written for "&" (Lasso_DB::get_url_details_by_url).
	 */
	public function key(): string {
		return strtolower( rtrim( str_replace( '&amp;', '&', $this->value ), '/' ) );
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
