<?php
/**
 * Keeps other plugins' notices off Affibloom's pages.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Module;

/**
 * Keeps other plugins' notices off Affibloom's pages, as Lasso does (F38 R15, D9): every admin_notices and
 * all_admin_notices callback (network and user ones too) and every in_admin_header callback except WordPress's admin
 * bar. Affibloom's own notices show in its region instead (NoticeBoard). WordPress's own pages are untouched.
 */
final class ForeignNotices implements Module {

	/**
	 * Builds the module.
	 *
	 * @param AdminNavigation $navigation Which admin pages are Affibloom's.
	 */
	public function __construct( private AdminNavigation $navigation ) {}

	/**
	 * Hooks into WordPress: late in admin_head, after other plugins have added theirs, and before WordPress prints the
	 * header and the notices.
	 */
	public function register(): void {
		add_action( 'admin_head', array( $this, 'remove' ), 99999 );
	}

	/**
	 * Removes the callbacks on an Affibloom page.
	 */
	public function remove(): void {
		if ( null === $this->navigation->current( AdminMenu::requested_page() ) ) {
			return;
		}
		foreach ( array( 'admin_notices', 'all_admin_notices', 'network_admin_notices', 'user_admin_notices', 'in_admin_header' ) as $hook ) {
			remove_all_actions( $hook );
		}
		// WordPress prints its admin bar from in_admin_header (default-filters.php), so it goes back.
		add_action( 'in_admin_header', 'wp_admin_bar_render', 0 );
	}
}
