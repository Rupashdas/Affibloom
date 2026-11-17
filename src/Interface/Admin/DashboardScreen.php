<?php
/**
 * The Dashboard: Lasso's main list of products.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Application\Dashboard\SavedViewService;
use Affibloom\Application\Groups\GroupService;
use Affibloom\Application\Products\ProductService;
use Affibloom\Domain\Product\LinkKind;
use Affibloom\Domain\Product\SearchTerm;
use Affibloom\Interface\Template;

/**
 * The Dashboard: Lasso's main list of products. The page prints the first list; Task 13D's REST route refreshes
 * it in place after that, through the same partial.
 */
final class DashboardScreen {

	/**
	 * Builds the screen.
	 *
	 * @param Template         $template Template renderer.
	 * @param ProductService   $products The product library.
	 * @param PerPage          $per_page How many products this editor sees at a time.
	 * @param SavedViewService $views    This editor's saved views (Task 13E).
	 * @param Notice           $notice   The toast after a redirect: "Product deleted." (Task 17A).
	 * @param GroupService     $groups   The Groups column, the Group filter and the pickers (Tasks G3, G5).
	 */
	public function __construct(
		private Template $template,
		private ProductService $products,
		private PerPage $per_page,
		private SavedViewService $views,
		private Notice $notice,
		private GroupService $groups,
	) {}

	/**
	 * Prints the page. WordPress has already checked the capability (Capabilities::PRODUCTS, in AdminMenu).
	 */
	public function render(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- a read-only list, with no action behind it.
		$asked = isset( $_GET['per_page'] ) ? absint( wp_unslash( $_GET['per_page'] ) ) : 0;
		$page  = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;
		$typed = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$kind  = isset( $_GET['link_type'] ) ? sanitize_key( wp_unslash( $_GET['link_type'] ) ) : '';
		$group = isset( $_GET['group'] ) ? (int) sanitize_text_field( wp_unslash( $_GET['group'] ) ) : 0;
		$one   = isset( $_GET['post_id'] ) ? absint( wp_unslash( $_GET['post_id'] ) ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$per_page  = $this->per_page->choose( $asked );
		$term      = SearchTerm::from_input( $typed );
		$link_kind = LinkKind::from_input( $kind );
		// A group that is gone (a bookmark, a saved view) falls back to all groups (F23 edge case 5).
		$group    = $this->groups->choose_filter( $group );
		$products = $this->products->list( $term, $link_kind, $page, $per_page, $group, $one );
		$ids      = array_map( static fn( $product ): int => (int) $product->id, $products->items );

		$this->template->render(
			'admin/dashboard',
			array(
				'products'   => $products,
				'term'       => $term->text(),
				'link_kind'  => $link_kind,
				'group'      => $group,
				'group_list' => $this->groups->all(),
				'groups_of'  => $this->groups->for_products( $ids ),
				'views'      => $this->views->list( get_current_user_id() ),
				'url'        => DashboardUrl::to( $term->text(), $link_kind, 1, $per_page, $group, $one ),
				// Every list argument but the page, for the GET forms' hidden inputs (Task 13F).
				'args'       => DashboardUrl::args( $term->text(), $link_kind, 1, $per_page, $group, $one ),
				'library'    => $this->products->count(),
				'notice'     => $this->notice->message(),
			)
		);
	}
}
