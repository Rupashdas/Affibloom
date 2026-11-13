<?php
/**
 * The Dashboard: Lasso's main list of products.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Application\Dashboard\SavedViewService;
use Affibloom\Application\Migration\LassoImportService;
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
	 * @param Template           $template Template renderer.
	 * @param ProductService     $products The product library.
	 * @param PerPage            $per_page How many products this editor sees at a time.
	 * @param SavedViewService   $views    This editor's saved views (Task 13E).
	 * @param Notice             $notice   The toast after a redirect: "Product deleted." (Task 17A).
	 * @param LassoImportService $import   The one-time Lasso import, whose card shows while it has records to bring in.
	 */
	public function __construct(
		private Template $template,
		private ProductService $products,
		private PerPage $per_page,
		private SavedViewService $views,
		private Notice $notice,
		private LassoImportService $import,
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
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$per_page  = $this->per_page->choose( $asked );
		$term      = SearchTerm::from_input( $typed );
		$link_kind = LinkKind::from_input( $kind );

		$this->template->render(
			'admin/dashboard',
			array(
				'products'      => $this->products->list( $term, $link_kind, $page, $per_page ),
				'term'          => $term->text(),
				'link_kind'     => $link_kind,
				'views'         => $this->views->list( get_current_user_id() ),
				'url'           => DashboardUrl::to( $term->text(), $link_kind, 1, $per_page ),
				'library'       => $this->products->count(),
				'notice'        => $this->notice->message(),
				'lasso_pending' => $this->import->pending(),
			)
		);
	}
}
