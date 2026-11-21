<?php
/**
 * The Dashboard: Lasso's main list of products.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Interface\PublicLinks;
use Affibloom\Application\Dashboard\SavedViewService;
use Affibloom\Application\Groups\GroupService;
use Affibloom\Application\LinkHealth\LinkIssueQuery;
use Affibloom\Application\Clicks\ClickReport;
use Affibloom\Application\Locations\LocationCounts;
use Affibloom\Application\Products\ProductService;
use Affibloom\Domain\Product\LinkKind;
use Affibloom\Domain\Product\Placement;
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
	 * @param Template            $template Template renderer.
	 * @param ProductService      $products The product library.
	 * @param PerPage             $per_page How many products this editor sees at a time.
	 * @param SavedViewService    $views    This editor's saved views (Task 13E).
	 * @param Notice              $notice   The toast after a redirect: "Product deleted." (Task 17A).
	 * @param GroupService        $groups   The Groups column, the Group filter and the pickers (Tasks G3, G5).
	 * @param PublicLinks|null    $links    The rows' public links (F24 R14; Task CL7).
	 * @param LinkIssueQuery|null $alerts The Alert column, its sort and the alert tabs (F11, Tasks 31A, 31B).
	 * @param LocationCounts|null $locations The Locations column (F12 L1-L3; Task 36A).
	 * @param ClickReport|null    $clicks    The Clicks column (F12 R10, D8; Task 34A).
	 */
	public function __construct(
		private Template $template,
		private ProductService $products,
		private PerPage $per_page,
		private SavedViewService $views,
		private Notice $notice,
		private GroupService $groups,
		private ?PublicLinks $links = null,
		private ?LinkIssueQuery $alerts = null,
		private ?LocationCounts $locations = null,
		private ?ClickReport $clicks = null,
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
		$order = isset( $_GET['order_by'], $_GET['order_type'] ) && 'detection_date' === $_GET['order_by'] ? sanitize_key( wp_unslash( $_GET['order_type'] ) ) : '';
		$tab   = isset( $_GET['filter'] ) ? sanitize_key( wp_unslash( $_GET['filter'] ) ) : '';
		$place = isset( $_GET['placements'] ) ? sanitize_key( wp_unslash( $_GET['placements'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$per_page  = $this->per_page->choose( $asked );
		$term      = SearchTerm::from_input( $typed );
		$link_kind = LinkKind::from_input( $kind );
		// A group that is gone (a bookmark, a saved view) falls back to all groups (F23 edge case 5).
		$group     = $this->groups->choose_filter( $group );
		$alerts    = $this->alerts?->listing( $order, $tab );
		$placement = Placement::from_input( $place );
		$products  = $this->products->list( $term, $link_kind, $page, $per_page, $group, $one, $alerts, $placement );
		$ids       = array_map( static fn( $product ): int => (int) $product->id, $products->items );

		$this->template->render(
			'admin/dashboard',
			array(
				'products'   => $products,
				'term'       => $term->text(),
				'link_kind'  => $link_kind,
				'group'      => $group,
				'placement'  => $placement,
				'group_list' => $this->groups->all(),
				'groups_of'  => $this->groups->for_products( $ids ),
				'links_of'   => null === $this->links ? array() : $this->links->of( $products->items ),
				'views'      => $this->views->list( get_current_user_id() ),
				'url'        => DashboardUrl::to( $term->text(), $link_kind, 1, $per_page, $group, $one, $alerts, $placement ),
				// Every list argument but the page, for the GET forms' hidden inputs (Task 13F).
				'args'       => DashboardUrl::args( $term->text(), $link_kind, 1, $per_page, $group, $one, $alerts, $placement ),
				'alerts_of'  => null === $this->alerts ? array() : $this->alerts->for_products( $ids ),
				'listing'    => $alerts,
				// The Locations column (Task 36A): each row's count, and whether a 0 is known yet (the clock).
				'locations'  => $this->locations?->of( $ids ),
				'known'      => $this->locations?->known() ?? true,
				'clicks'     => $this->clicks?->totals_for( $ids, time() ),
				'counts'     => null === $this->alerts ? null : $this->alerts->counts( $term, $link_kind, $group, $one, $placement ),
				'library'    => $this->products->count(),
				'notice'     => $this->notice->message(),
			)
		);
	}
}
