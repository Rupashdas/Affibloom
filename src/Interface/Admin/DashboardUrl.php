<?php
/**
 * The Dashboard's own address for a list: the search, the page and the size as query arguments.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Application\Products\ProductService;
use Affibloom\Domain\Product\LinkKind;

/**
 * The Dashboard's own address for a list. The page and the REST route that refreshes it (Task 13D) both build it
 * here, because inside a REST request "the current URL" is the REST route's, which would make the pager's links,
 * and the address pushed into the browser's history, point at the API instead of the screen.
 */
final class DashboardUrl {

	/**
	 * The address of this list. Arguments at their defaults are left out, so the plain Dashboard stays
	 * `admin.php?page=affibloom`.
	 *
	 * @param string   $term      The cleaned search text; '' for none.
	 * @param LinkKind $link_kind The link-type filter.
	 * @param int      $page      Page number, from 1.
	 * @param int      $per_page  Products per page.
	 */
	public static function to( string $term, LinkKind $link_kind, int $page, int $per_page ): string {
		$args = array( 'page' => AdminMenu::SLUG );
		if ( '' !== $term ) {
			$args['s'] = $term;
		}
		if ( LinkKind::All !== $link_kind ) {
			$args['link_type'] = $link_kind->value;
		}
		if ( ProductService::PER_PAGE_DEFAULT !== $per_page ) {
			$args['per_page'] = $per_page;
		}
		if ( $page > 1 ) {
			$args['paged'] = $page;
		}

		return add_query_arg( array_map( 'rawurlencode', array_map( 'strval', $args ) ), admin_url( 'admin.php' ) );
	}
}
