<?php
/**
 * A link location's identity across rescans.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\LinkIndex;

/**
 * A location is the n-th link with the same URL in the same host and source (F21 D4): no stamp is written into
 * content, so a rescan finds the same row again by these four values (the table's unique key).
 */
final class LocationKey {

	/**
	 * Lasso's md5_link_slug (R2): the URL without its trailing slashes.
	 *
	 * @param string $url The link as written.
	 */
	public static function md5( string $url ): string {
		return md5( rtrim( $url, '/' ) );
	}

	/**
	 * The four values as one string, for matching a stored row with a new one.
	 *
	 * @param int    $host_id    The host post.
	 * @param string $source     Where in the host.
	 * @param string $url_md5    LocationKey::md5() of the URL.
	 * @param int    $occurrence 1 for the first such link.
	 */
	public static function of( int $host_id, string $source, string $url_md5, int $occurrence ): string {
		return $host_id . '|' . $source . '|' . $url_md5 . '|' . $occurrence;
	}
}
