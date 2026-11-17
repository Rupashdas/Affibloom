<?php
/**
 * The link-shortening or affiliate platform a link goes through.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\LinkIndex;

/**
 * Lasso's affiliate_platform (F21 R2): Skimlinks, Genius, TinyURL, or any "go." subdomain, read from the link's host.
 */
final class AffiliatePlatform {

	/** Skimlinks' redirect hosts. */
	private const SKIMLINKS = array( 'fave.co', 'go.skimresources.com', 'go.redirectingat.com', 'go.effortlessgent.com' );

	/** Genius's hosts. */
	private const GENIUS = array( 'geni.us', 'buy.geni.us', 'urlgeni.us' );

	/**
	 * "skimlinks", "geni.us", "tinyurl", "go", or null for any other link.
	 *
	 * @param string $url The link as written.
	 */
	public static function of( string $url ): ?string {
		$host = strtolower( (string) parse_url( $url, PHP_URL_HOST ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Domain code does not call WordPress.
		$host = str_starts_with( $host, 'www.' ) ? substr( $host, 4 ) : $host;

		return match ( true ) {
			'' === $host => null,
			in_array( $host, self::SKIMLINKS, true ) => 'skimlinks',
			in_array( $host, self::GENIUS, true ) => 'geni.us',
			'tinyurl.com' === $host => 'tinyurl',
			str_starts_with( $host, 'go.' ) => 'go',
			default => null,
		};
	}
}
