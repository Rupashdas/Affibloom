<?php
/**
 * The Dashboard: Lasso's main list of products.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Application\Products\ProductService;
use Affibloom\Interface\Template;

/**
 * The Dashboard: Lasso's main list of products.
 */
final class DashboardScreen {

	/**
	 * Builds the screen.
	 *
	 * @param Template       $template Template renderer.
	 * @param Notice         $notice   The toast after a redirect, e.g. after "Add A New Link".
	 * @param ProductService $products The product library.
	 * @param PerPage        $per_page How many products this editor sees at a time.
	 */
	public function __construct(
		private Template $template,
		private Notice $notice,
		private ProductService $products,
		private PerPage $per_page,
	) {}

	/**
	 * Prints the page. WordPress has already checked the capability (Capabilities::PRODUCTS, in AdminMenu).
	 */
	public function render(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- a read-only list, with no action behind it.
		$asked = isset( $_GET['per_page'] ) ? absint( wp_unslash( $_GET['per_page'] ) ) : 0;
		$page  = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$per_page = $this->per_page->choose( $asked );

		$this->template->render(
			'admin/dashboard',
			array(
				'products'    => $this->products->list( $page, $per_page ),
				'notice'      => $this->notice->message(),
				'notice_kind' => $this->notice->kind(),
			)
		);
	}
}
