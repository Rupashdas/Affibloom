<?php
/**
 * An Amazon link: its ASIN, its store's country, and the form Lasso stores it in.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Amazon;

use Affibloom\Domain\Product\AffiliateUrl;

/**
 * An Amazon link (F2 requirements, sections 2-3): its ASIN, its store's country, its search keywords, and the form
 * Lasso stores it in. Short links are followed before this (Task 12D).
 */
final class AmazonUrl {

	/**
	 * Lasso_Amazon_Api::get_product_id_by_url(): the path markers that carry an ASIN, matched on what follows the host.
	 * The greedy "(?:/.*)?" makes the last marker count, as in Lasso. Lasso matches the host case-sensitively in the
	 * same pattern; here the host is already known to be Amazon's (AffiliateUrl::is_amazon(), without case).
	 */
	private const ASIN_PATTERN = '#^(?:/.*)?(?:/gp/aw/d/|/d/[a-zA-Z0-9\-]*/[a-zA-Z0-9\-]*/|/dp/|/clp/|/gp/product/|/ASIN/|/gp/video/detail/)([a-zA-Z0-9]*)#';

	/** Lasso_Amazon_Api::get_aff_link_and_flag(): a store's host and its country code. */
	private const COUNTRIES = array(
		'www.amazon.com'    => 'us',
		'www.amazon.ca'     => 'ca',
		'www.amazon.com.br' => 'br',
		'www.amazon.com.mx' => 'mx',
		'www.amazon.fr'     => 'fr',
		'www.amazon.de'     => 'de',
		'www.amazon.it'     => 'it',
		'www.amazon.es'     => 'es',
		'www.amazon.co.uk'  => 'gb',
		'www.amazon.cn'     => 'cn',
		'www.amazon.co.jp'  => 'jp',
		'www.amazon.in'     => 'in',
		'www.amazon.com.au' => 'au',
		'www.amazon.ae'     => 'ae',
		'www.amazon.nl'     => 'nl',
		'www.amazon.se'     => 'se',
		'www.amazon.sg'     => 'sg',
		'www.amazon.com.tr' => 'tr',
		'www.amazon.pl'     => 'pl',
		'www.amazon.eg'     => 'eg',
	);

	/** Lasso_Amazon_Api::get_country_by_url()'s answer for any other host. */
	private const DEFAULT_COUNTRY = 'us';

	/** Lasso_Amazon_Api::keep_args(): the parameters a shortened product link keeps, in this order. */
	private const KEPT_PARAMETERS = array( 'maas', 'ref_', 's', 'aa_campaignid', 'aa_creativeid', 'aa_adgroupid', 'campaignId', 'linkCode', 'linkId' );

	/** Lasso_Amazon_Api::is_amazon_search_page(): the parameters that hold a search's keywords, in Lasso's preference. */
	private const KEYWORD_PARAMETERS = array( 'k', 'field-keywords', 'keywords' );

	/** Lasso_Amazon_Api::is_amazon_shortened_url(): Amazon's short-link hosts. */
	private const SHORT_LINK_HOSTS = array( 'amzn.to', 'amzn.com', 'amzlink.to' );

	/**
	 * Use from().
	 *
	 * @param AffiliateUrl $url An Amazon link.
	 */
	private function __construct( private readonly AffiliateUrl $url ) {}

	/**
	 * The link as an Amazon link, or null when it is not one (its host is not in Lasso's Amazon list).
	 *
	 * @param AffiliateUrl $url Any link.
	 */
	public static function from( AffiliateUrl $url ): ?self {
		return $url->is_amazon() ? new self( $url ) : null;
	}

	/**
	 * The ASIN as written (Lasso changes neither its case nor checks its length), or '' when the link has none.
	 */
	public function asin(): string {
		$after_host = (string) preg_replace( '#^[a-z][a-z0-9+.\-]*://[^/?\#]*#i', '', $this->url->value() );
		if ( ! preg_match( self::ASIN_PATTERN, $after_host, $match ) ) {
			return '';
		}
		// Lasso tests the match with empty(), which a lone "0" fails too.
		return '0' === $match[1] ? '' : $match[1];
	}

	/**
	 * The store's country code ("us", "gb", …); "us" for any other Amazon host.
	 */
	public function country(): string {
		return self::COUNTRIES[ 'www.' . $this->url->host() ] ?? self::DEFAULT_COUNTRY;
	}

	/**
	 * Lasso_Helper::is_amazon_sponsored_url(): Amazon's own attribution links, which Lasso leaves exactly as pasted —
	 * a "maas" parameter with tag "maas", a Creator Connections campaign, or linkCode "tr1" with a linkId.
	 */
	public function is_protected(): bool {
		$query = self::query_of( $this->url->value() );

		$maas = self::text( $query, 'maas' );
		if ( self::filled( $maas ) && 'maas' === strtolower( self::text( $query, 'tag' ) ) ) {
			return true;
		}
		if ( str_starts_with( self::text( $query, 'campaignId' ), 'amzn1.campaign.' ) ) {
			return true;
		}
		return 'tr1' === self::text( $query, 'linkCode' ) && '' !== self::text( $query, 'linkId' );
	}

	/**
	 * The link to store (Lasso_Amazon_Api::get_amazon_product_url() with monetizing on, after format_amazon_url()):
	 * - short links and protected links stay as pasted;
	 * - a page with no ASIN (a search, a store, a list) keeps its whole query with the tag set where it is, or added
	 *   last; with no tag to set it stays as it is (Lasso's search branch needs a tag; its other-page branch would
	 *   only rewrite the encoding);
	 * - in a product link, "smile.amazon." becomes "amazon.";
	 * - a product link, shortened: the path as pasted, then the tag, then Lasso's kept parameters in its order (no tag
	 *   with "maas"); not shortened: the whole query with the tag set where it is, or added last.
	 * Three improvements, none changing a link Lasso would store intact: "&amp;" is read as "&" in both forms (Lasso
	 * does so only when shortening); a fragment is dropped (Lasso drops it when there is a query and puts the query
	 * after it when there is none, which breaks the tag); kept values are encoded again (Lasso writes them decoded,
	 * so a value holding "&" or "#" would break the link). Tracking IDs and the live values are unaffected.
	 *
	 * @param TrackingIds $ids     The tag rule.
	 * @param bool        $shorten Settings → Amazon, "Shorten Amazon affiliate links".
	 */
	public function monetized( TrackingIds $ids, bool $shorten ): AffiliateUrl {
		if ( $this->is_short_link() || $this->is_protected() ) {
			return $this->url;
		}

		if ( '' === $this->asin() ) {
			$link  = str_replace( '&amp;', '&', $this->url->value() );
			$query = self::query_of( $link );
			$tag   = $ids->tag_for( self::text( $query, 'tag' ) );
			return '' === $tag ? $this->url : AffiliateUrl::from_string( self::with_tag( $link, $query, $tag ) );
		}

		$link  = str_replace( array( 'smile.amazon.', '&amp;' ), array( 'amazon.', '&' ), $this->url->value() );
		$query = self::query_of( $link );
		$tag   = $ids->tag_for( self::text( $query, 'tag' ) );

		return AffiliateUrl::from_string( $shorten ? self::shortened( $link, $query, $tag ) : self::with_tag( $link, $query, $tag ) );
	}

	/**
	 * Lasso_Amazon_Api::is_amazon_search_page(): a search's keywords — no ASIN, a path ending in "/s" or containing
	 * "/s/", and a "k", "field-keywords" or "keywords" parameter (the first that has a value) — or ''.
	 */
	public function search_keywords(): string {
		if ( '' !== $this->asin() ) {
			return '';
		}

		$path = rtrim( (string) parse_url( $this->url->value(), PHP_URL_PATH ), '/' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Domain code does not call WordPress.
		if ( ! str_ends_with( $path, '/s' ) && ! str_contains( $path, '/s/' ) ) {
			return '';
		}

		$query = self::query_of( $this->url->value() );
		foreach ( self::KEYWORD_PARAMETERS as $name ) {
			if ( self::filled( self::text( $query, $name ) ) ) {
				return self::text( $query, $name );
			}
		}
		return '';
	}

	/**
	 * What Lasso finds a search page again by (get_amazon_search_match_key(): its base_domain and search_text): the
	 * host without "www." and the keywords with entities decoded, percent-decoded once more, trimmed and their spaces
	 * collapsed; '' for any other link. Stored as "<host>|<keywords>"; the lookup compares it without case, as Lasso's
	 * MySQL columns do.
	 */
	public function search_key(): string {
		$keywords = $this->search_keywords();
		if ( '' === $keywords ) {
			return '';
		}

		$text = trim( rawurldecode( html_entity_decode( $keywords, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
		$text = (string) preg_replace( '/\s+/', ' ', $text );
		return '' === $text ? '' : $this->url->host() . '|' . $text;
	}

	/**
	 * Lasso_Amazon_Api::get_search_page_title(): "Amazon: <keywords>" for a search page, else "Amazon".
	 */
	public function search_page_title(): string {
		$keywords = $this->search_keywords();
		return '' === $keywords ? 'Amazon' : 'Amazon: ' . $keywords;
	}

	/**
	 * Whether the host is one of Amazon's short-link hosts. Lasso looks for the three names anywhere in the link;
	 * here only the host counts, so a store link mentioning "amzn.to" in its query is still a store link. Public on its
	 * second use: ProductService types only store links as Amazon until Task 12D follows short links.
	 */
	public function is_short_link(): bool {
		return in_array( $this->url->host(), self::SHORT_LINK_HOSTS, true );
	}

	/**
	 * A shortened product link: everything before the query, then "tag=…" unless "maas" is present, then the kept
	 * parameters that have a value.
	 *
	 * @param string                  $link  The link.
	 * @param array<array-key, mixed> $query Its parsed query.
	 * @param string                  $tag   The tag to store, or ''.
	 */
	private static function shortened( string $link, array $query, string $tag ): string {
		$base = self::without_query( $link );

		$args = array();
		if ( '' !== $tag && ! self::filled( self::text( $query, 'maas' ) ) ) {
			$args[] = 'tag=' . rawurlencode( $tag );
		}
		foreach ( self::KEPT_PARAMETERS as $name ) {
			$value = self::text( $query, $name );
			if ( self::filled( $value ) ) {
				$args[] = $name . '=' . rawurlencode( $value );
			}
		}

		return array() === $args ? $base : $base . '?' . implode( '&', $args );
	}

	/**
	 * The link with its whole query and the tag set (Lasso_Helper::add_params_to_url(), get_query_from_array() and
	 * get_url_from_parse()): empty values are left out, the rest written with rawurlencode, keys as parsed.
	 *
	 * @param string                  $link  The link.
	 * @param array<array-key, mixed> $query Its parsed query.
	 * @param string                  $tag   The tag to store, or '' to leave none.
	 */
	private static function with_tag( string $link, array $query, string $tag ): string {
		$query['tag'] = $tag;

		$pairs = array();
		foreach ( $query as $key => $value ) {
			if ( is_array( $value ) ) {
				foreach ( $value as $index => $item ) {
					if ( is_scalar( $item ) ) {
						$pairs[] = $key . '[' . $index . ']=' . rawurlencode( (string) $item );
					}
				}
			} elseif ( is_string( $value ) && '' !== $value ) {
				$pairs[] = $key . '=' . rawurlencode( $value );
			}
		}

		$base = self::without_query( $link );
		return array() === $pairs ? $base : $base . '?' . implode( '&', $pairs );
	}

	/**
	 * Everything before a link's query and fragment.
	 *
	 * @param string $link A link.
	 */
	private static function without_query( string $link ): string {
		return explode( '#', explode( '?', $link, 2 )[0], 2 )[0];
	}

	/**
	 * A link's parsed query (what follows "?" and comes before "#"), as PHP's parse_str reads it.
	 *
	 * @param string $link A link.
	 * @return array<array-key, mixed>
	 */
	private static function query_of( string $link ): array {
		$query = (string) parse_url( str_replace( '&amp;', '&', $link ), PHP_URL_QUERY ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Domain code does not call WordPress.
		parse_str( $query, $values );
		return $values;
	}

	/**
	 * A query parameter as text, '' when it is missing or a list.
	 *
	 * @param array<array-key, mixed> $query A parsed query.
	 * @param string                  $name  Parameter name.
	 */
	private static function text( array $query, string $name ): string {
		return isset( $query[ $name ] ) && is_string( $query[ $name ] ) ? $query[ $name ] : '';
	}

	/**
	 * Whether PHP (and so Lasso's "if ( $value )") would read a parameter value as present: not '' and not "0".
	 *
	 * @param string $value A parameter value.
	 */
	private static function filled( string $value ): bool {
		return '' !== $value && '0' !== $value;
	}
}
