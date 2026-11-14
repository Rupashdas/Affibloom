<?php
/**
 * The Groups list's own address: the search and the page as query arguments.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

/**
 * The Groups list's own address. The screen and the REST route that refreshes the list in place (Task G1) both build it
 * here, because inside a REST request "the current URL" is the route's, which would point the page links, and the
 * address put in the browser's history, at the API (the Dashboard's DashboardUrl is the first case).
 */
final class GroupsUrl {

	/**
	 * The address of this list. Arguments at their defaults are left out, so the plain list stays
	 * `admin.php?page=affibloom-groups`.
	 *
	 * @param string $term The cleaned search text; '' for none.
	 * @param int    $page Page number, from 1.
	 */
	public static function to( string $term, int $page ): string {
		$args = array( 'page' => GroupsScreen::SLUG );
		if ( '' !== $term ) {
			$args['s'] = $term;
		}
		if ( $page > 1 ) {
			$args['paged'] = $page;
		}

		return add_query_arg( array_map( 'rawurlencode', array_map( 'strval', $args ) ), admin_url( 'admin.php' ) );
	}
}
