<?php
/**
 * "There's a new version available. Update now."
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Shell;

use Affibloom\Domain\Shell\ShellNotice;

/**
 * "There's a new version available. Update now." on every Affibloom page while WordPress lists an update (F38 R24,
 * D11). Like Lasso's, it has no ×. Affibloom has no update server, so it shows only if one is ever configured.
 */
final class UpdateAvailableNotice implements NoticeProvider {

	/**
	 * Builds the provider.
	 *
	 * @param UpdateSource $updates WordPress's list of plugin updates.
	 */
	public function __construct( private UpdateSource $updates ) {}

	/**
	 * The notice, or none.
	 *
	 * @param string $page The Affibloom admin page slug being shown (every page shows it).
	 * @return list<ShellNotice>
	 */
	public function notices( string $page ): array {
		if ( ! $this->updates->available() ) {
			return array();
		}

		return array( new ShellNotice( 'update_available', ShellNotice::UPDATE, array(), $this->updates->update_url(), false ) );
	}
}
