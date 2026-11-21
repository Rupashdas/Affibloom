<?php
/**
 * The repeat guard in transients.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Application\Clicks\RepeatGuard;

/**
 * ADR-005's guard (F12 D6; Task 33B): an `affibloom_c_<hash>` transient for 30 seconds, the hash a keyed SHA-256 of the
 * click's key with the site's salt, so the stored name says nothing about the reader.
 */
final class TransientRepeatGuard implements RepeatGuard {

	/**
	 * Whether this is the first such click in the last 30 seconds.
	 *
	 * @param string $key What makes two clicks the same.
	 */
	public function first( string $key ): bool {
		$name = 'affibloom_c_' . substr( hash_hmac( 'sha256', $key, wp_salt( 'nonce' ) ), 0, 32 );
		if ( false !== get_transient( $name ) ) {
			return false;
		}
		set_transient( $name, 1, self::SECONDS );

		return true;
	}
}
