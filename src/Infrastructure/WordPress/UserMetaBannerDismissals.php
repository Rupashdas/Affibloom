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
 * A banner's dismissals in user meta: the bulk sync banner's `affibloom_bulk_sync_dismissed` (Lasso's
 * lasso_dismiss_amazon_bulk_sync_banner_v2), and since its second use the Amazon import notice's (Task AM4).
 */
final class UserMetaBannerDismissals implements BannerDismissals {

	public const META = 'affibloom_bulk_sync_dismissed';

	/**
	 * Builds the store.
	 *
	 * @param string $meta The user meta key: the bulk sync banner's, or the Amazon import notice's (Task AM4,
	 *                     `affibloom_amazon_import_dismissed`).
	 */
	public function __construct( private string $meta = self::META ) {}

	/**
	 * The fingerprint a person dismissed, or ''.
	 *
	 * @param int $user The person's user ID.
	 */
	public function get( int $user ): string {
		$value = get_user_meta( $user, $this->meta, true );

		return is_string( $value ) ? $value : '';
	}

	/**
	 * Remembers the fingerprint a person dismissed.
	 *
	 * @param int    $user        The person's user ID.
	 * @param string $fingerprint The pending set's fingerprint.
	 */
	public function set( int $user, string $fingerprint ): void {
		update_user_meta( $user, $this->meta, $fingerprint );
	}
}
