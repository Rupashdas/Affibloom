<?php
/**
 * Which pending set each person hid the bulk sync banner for.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Amazon;

/**
 * Which pending set each person hid the bulk sync banner for (C1 R41: the fingerprint of the pending IDs, per user).
 */
interface BannerDismissals {

	/**
	 * The fingerprint a person dismissed, or ''.
	 *
	 * @param int $user The person's user ID.
	 */
	public function get( int $user ): string;

	/**
	 * Remembers the fingerprint a person dismissed.
	 *
	 * @param int    $user        The person's user ID.
	 * @param string $fingerprint The pending set's fingerprint.
	 */
	public function set( int $user, string $fingerprint ): void;
}
