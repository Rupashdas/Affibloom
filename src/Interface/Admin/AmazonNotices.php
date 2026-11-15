<?php
/**
 * C1's notices in the region under the header.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Application\Amazon\BulkSyncService;
use Affibloom\Application\Shell\NoticeProvider;
use Affibloom\Domain\Shell\ShellNotice;

/**
 * C1's notices in the region under the header of every Affibloom page (Task ON2's NoticeBoard). The first is the bulk
 * Amazon sync banner (C1I; Lasso's #lasso-bulk-sync-amazon-banner, R41): the pending count, whether a run is going,
 * and whether Amazon can be asked; partials/amazon-bulk-sync.php draws it. C1N's SiteStripe notice is its own
 * provider (SiteStripeNotice), drawn as any other notice.
 */
final class AmazonNotices implements NoticeProvider {

	public const BULK_SYNC = 'amazon_bulk_sync';

	/**
	 * Builds the provider.
	 *
	 * @param BulkSyncService $sync Bulk Amazon sync.
	 */
	public function __construct( private BulkSyncService $sync ) {}

	/**
	 * The banner for the signed-in person, while products are pending and they have not dismissed that set. Only people
	 * who edit products see it: its routes need that capability.
	 *
	 * @param string $page The Affibloom admin page slug being shown.
	 * @return list<ShellNotice>
	 */
	public function notices( string $page ): array {
		unset( $page ); // On every Affibloom page, as Lasso's is_lasso_page().
		if ( ! current_user_can( Capabilities::PRODUCTS ) ) {
			return array();
		}
		$banner = $this->sync->banner( get_current_user_id() );
		if ( null === $banner ) {
			return array();
		}

		return array(
			new ShellNotice(
				self::BULK_SYNC,
				ShellNotice::UPDATE,
				array( $banner['pending'], $banner['running'] ? 1 : 0, $banner['can_sync'] ? 1 : 0 )
			),
		);
	}
}
