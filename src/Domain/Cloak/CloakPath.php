<?php
/**
 * Which product slug a request path names.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Cloak;

/**
 * Lasso's matching (F24 R16, R17, D4, N6): the request path without its query and without the site's subdirectory;
 * none with `/favicon.ico` or `/admin`; with a Cloaked Link Prefix only `/<prefix>/<slug>/`, without one only
 * `/<slug>/` (longer paths are not matched, N6). The slug is compared as WordPress stores post names.
 */
final class CloakPath {

	/**
	 * The slug the path names, or null when it names none.
	 *
	 * @param string $uri       The request URI ("/go/aircat/?x=1").
	 * @param string $prefix    The Cloaked Link Prefix, '' for none.
	 * @param string $home_path The site's path ("/blog" for a site in a subdirectory, '' at the root).
	 */
	public static function slug( string $uri, string $prefix, string $home_path ): ?string {
		$path = (string) strtok( $uri, '?#' );
		if ( str_contains( $path, '/favicon.ico' ) || str_contains( $path, '/admin' ) ) {
			return null;
		}
		$home = trim( $home_path, '/' );
		$path = trim( rawurldecode( $path ), '/' );
		if ( '' !== $home ) {
			if ( ! str_starts_with( $path . '/', $home . '/' ) ) {
				return null;
			}
			$path = trim( substr( $path, strlen( $home ) ), '/' );
		}
		$parts = '' === $path ? array() : explode( '/', $path );

		$prefix = trim( $prefix, '/' );
		if ( '' !== $prefix ) {
			return 2 === count( $parts ) && $prefix === $parts[0] && '' !== $parts[1] ? strtolower( $parts[1] ) : null;
		}

		return 1 === count( $parts ) ? strtolower( $parts[0] ) : null;
	}
}
