<?php
/**
 * The bulk sync banner's dismissals in user meta.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Application\Amazon\BannerDismissals;

/**
 * The bulk sync banner's dismissals in user meta `affibloom_bulk_sync_dismissed` (Lasso's
 * lasso_dismiss_amazon_bulk_sync_banner_v2).
 */
final class UserMetaBannerDismissals implements BannerDismissals {

	public const META = 'affibloom_bulk_sync_dismissed';

	/**
	 * The fingerprint a person dismissed, or ''.
	 *
	 * @param int $user The person's user ID.
	 */
	public function get( int $user ): string {
		$value = get_user_meta( $user, self::META, true );

		return is_string( $value ) ? $value : '';
	}

	/**
	 * Remembers the fingerprint a person dismissed.
	 *
	 * @param int    $user        The person's user ID.
	 * @param string $fingerprint The pending set's fingerprint.
	 */
	public function set( int $user, string $fingerprint ): void {
		update_user_meta( $user, self::META, $fingerprint );
	}
}
