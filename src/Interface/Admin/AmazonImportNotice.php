<?php
/**
 * The Amazon import's notice in the region under the header.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Application\Monetize\AmazonImportStatusService;
use Affibloom\Application\Shell\NoticeProvider;
use Affibloom\Domain\Shell\ShellNotice;

/**
 * Lasso's Amazon import banner (F35 R30, R31, D19; Task AM4; `#lasso-amazon-auto-monetize-banner`) on every Affibloom
 * page, in the notice region (Task ON2's NoticeBoard): while Auto-Monetization is on and the import has work, unless
 * this person dismissed these counts. partials/amazon-import-notice.php draws it; amazon-import-status.js keeps it
 * current.
 */
final class AmazonImportNotice implements NoticeProvider {

	public const KEY = 'amazon_import';

	/**
	 * Builds the provider.
	 *
	 * @param AmazonImportStatusService $status The import's status.
	 */
	public function __construct( private AmazonImportStatusService $status ) {}

	/**
	 * The notice for the signed-in person, with the status's figures as its arguments.
	 *
	 * @param string $page The Affibloom admin page slug being shown.
	 * @return list<ShellNotice>
	 */
	public function notices( string $page ): array {
		if ( ! current_user_can( Capabilities::PRODUCTS ) ) {
			return array();
		}
		// The banner on every Affibloom page, as Lasso's is_lasso_page(); on the Dashboard, once the import is idle, the
		// first day's explanation instead (R32; Task AM5), which stays until it is dismissed.
		$status  = $this->status->notice( get_current_user_id() );
		$context = 0;
		if ( null === $status && AdminMenu::SLUG === $page ) {
			$status  = $this->status->dashboard_notice( get_current_user_id(), time() );
			$context = 1;
		}
		if ( null === $status ) {
			return array();
		}

		return array(
			new ShellNotice(
				self::KEY,
				ShellNotice::UPDATE,
				array( $status->queue, $status->dashboard, $status->indexed, $status->running ? 1 : 0, $status->total, $status->completed, $context )
			),
		);
	}
}
