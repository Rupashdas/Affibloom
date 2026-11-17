<?php
/**
 * The query string a cloaked redirect passes on.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Cloak;

/**
 * Lasso's pass-through (F24 R22, D7, N5; Lasso_Redirect::pass_through_url_parameters without its cloud call):
 * - a reader's `sid`, arriving from another site, for a destination of a known network, becomes that network's first
 *   sub-ID parameter (and is not passed on as `sid`);
 * - every other parameter of the request fills one the destination lacks; a `utm_*` one always replaces the
 *   destination's; `doing_wp_cron`, `lcid` (Lasso's script's click ID) and empty values are dropped;
 * - a destination parameter whose value is `{url}` becomes the referring page without its query (else the site's
 *   address), `{url_full}` the same plus the referrer's own parameters;
 * - the query is rebuilt as Lasso does (`urldecode( http_build_query() )`), the fragment kept; with nothing changed the
 *   destination comes back exactly as it was.
 */
final class RedirectQuery {

	/**
	 * The destination with the request's parameters applied.
	 *
	 * @param string                $destination The product's destination.
	 * @param array<string, string> $request     The request's parameters (first values, as received).
	 * @param string                $referer     The referring page, '' for none.
	 * @param string                $site_url    The site's address.
	 * @param NetworkSubIds         $networks    The known networks' sub-ID parameters.
	 */
	public static function apply( string $destination, array $request, string $referer, string $site_url, NetworkSubIds $networks ): string {
		$parts = parse_url( $destination ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Domain code does not call WordPress.
		if ( ! is_array( $parts ) || ! isset( $parts['scheme'], $parts['host'] ) ) {
			return $destination;
		}
		parse_str( (string) ( $parts['query'] ?? '' ), $params );
		$changed = false;

		$sid = (string) ( $request['sid'] ?? '' );
		if ( '' !== $sid && self::host( $referer ) !== self::host( $site_url ) ) {
			$sub_id = $networks->first_for( $destination );
			if ( null !== $sub_id ) {
				$params[ $sub_id ] = $sid;
				$changed           = true;
				unset( $request['sid'] );
			}
		}

		foreach ( $request as $key => $value ) {
			$key = (string) $key;
			if ( in_array( $key, array( 'doing_wp_cron', 'lcid' ), true ) || '' === (string) $value ) {
				continue;
			}
			if ( str_starts_with( strtolower( $key ), 'utm_' ) || ! isset( $params[ $key ] ) ) {
				$params[ $key ] = (string) $value;
				$changed        = true;
			}
		}

		$from = '' !== $referer ? $referer : $site_url;
		foreach ( $params as $key => $value ) {
			if ( '{url}' === $value || '{url_full}' === $value ) {
				$params[ $key ] = rtrim( (string) strtok( $from, '?#' ), '/' );
				if ( '{url_full}' === $value ) {
					parse_str( (string) parse_url( $from, PHP_URL_QUERY ), $referer_params ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Domain code does not call WordPress.
					$params = array_merge( $params, $referer_params );
				}
				$changed = true;
			}
		}

		if ( ! $changed ) {
			return $destination;
		}

		return $parts['scheme'] . '://' . $parts['host']
			. ( isset( $parts['port'] ) ? ':' . $parts['port'] : '' )
			. ( $parts['path'] ?? '' )
			. '?' . urldecode( http_build_query( $params ) )
			. ( isset( $parts['fragment'] ) ? '#' . $parts['fragment'] : '' );
	}

	/**
	 * A site's host without "www.", as Lasso's get_base_domain().
	 *
	 * @param string $url An address.
	 */
	private static function host( string $url ): string {
		$host = strtolower( (string) parse_url( $url, PHP_URL_HOST ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Domain code does not call WordPress.

		return str_replace( 'www.', '', $host );
	}
}
