<?php
/**
 * The Dashboard's own address for a list: the search, the filters, the page and the size as query arguments.
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
	 * @param int      $group     The Group filter (Task G5): 0 for all groups, -1 for products in no group, else a group's ID.
	 * @param int      $post_id   One product only (Lasso's `?post_id=`, Task 13F; F19 D24); 0 for all.
	 */
	public static function to( string $term, LinkKind $link_kind, int $page, int $per_page, int $group = 0, int $post_id = 0 ): string {
		$args = array( 'page' => AdminMenu::SLUG ) + self::args( $term, $link_kind, $page, $per_page, $group, $post_id );

		return add_query_arg( array_map( 'rawurlencode', $args ), admin_url( 'admin.php' ) );
	}

	/**
	 * The list's query arguments without the screen's `page`, each left out at its default. The address above uses
	 * them, and so do the hidden inputs that let the screen's GET forms keep every other argument without JavaScript
	 * (Task 13F; F19 D17).
	 *
	 * @param string   $term      As to().
	 * @param LinkKind $link_kind As to().
	 * @param int      $page      As to().
	 * @param int      $per_page  As to().
	 * @param int      $group     As to().
	 * @param int      $post_id   As to().
	 * @return array<string, string>
	 */
	public static function args( string $term, LinkKind $link_kind, int $page, int $per_page, int $group = 0, int $post_id = 0 ): array {
		$args = array();
		if ( '' !== $term ) {
			$args['s'] = $term;
		}
		if ( LinkKind::All !== $link_kind ) {
			$args['link_type'] = $link_kind->value;
		}
		if ( 0 !== $group ) {
			$args['group'] = (string) $group;
		}
		if ( $post_id > 0 ) {
			$args['post_id'] = (string) $post_id;
		}
		if ( ProductService::PER_PAGE_DEFAULT !== $per_page ) {
			$args['per_page'] = (string) $per_page;
		}
		if ( $page > 1 ) {
			$args['paged'] = (string) $page;
		}

		return $args;
	}
}
