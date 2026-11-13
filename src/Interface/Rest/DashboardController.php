<?php
/**
 * The Dashboard's list without a page reload: affibloom/v1/dashboard/products.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Application\Products\ProductService;
use Affibloom\Domain\Product\SearchTerm;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Interface\Admin\DashboardUrl;
use Affibloom\Interface\Admin\PerPage;
use Affibloom\Interface\Template;
use Affibloom\Module;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * The Dashboard's list without a page reload (Task 13D), as Lasso's list is an AJAX view (`lasso_report_urls`).
 *
 * The response carries the list region's markup, rendered by the same partial the page prints, so markup and
 * escaping stay in PHP and there is no second row template in JavaScript. It also carries the screen's own
 * address for the list, which the script pushes into the browser's history so reload, Back and bookmarks keep
 * working.
 */
final class DashboardController implements Module {

	/**
	 * Builds the controller.
	 *
	 * @param ProductService $products The product library.
	 * @param PerPage        $per_page How many products this editor sees at a time.
	 * @param Template       $template Renders the list partial.
	 */
	public function __construct(
		private ProductService $products,
		private PerPage $per_page,
		private Template $template,
	) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
	}

	/**
	 * Registers the route.
	 */
	public function routes(): void {
		register_rest_route(
			ProductsController::NAMESPACE,
			'/dashboard/products',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'products' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => array(
						's'        => array(
							'type'    => 'string',
							'default' => '',
						),
						'paged'    => array(
							'type'    => 'integer',
							'minimum' => 1,
							'default' => 1,
						),
						// 0 asks for the size this editor chose last (PerPage).
						'per_page' => array(
							'type'    => 'integer',
							'minimum' => 0,
							'default' => 0,
						),
					),
				),
			)
		);
	}

	/**
	 * Only people who manage products see the list, as on the screen itself.
	 */
	public function can_manage(): bool {
		return current_user_can( Capabilities::PRODUCTS );
	}

	/**
	 * GET /dashboard/products: one page of the list, as the screen would print it.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request Request with s, paged and per_page.
	 */
	public function products( WP_REST_Request $request ): WP_REST_Response {
		$term     = SearchTerm::from_input( sanitize_text_field( (string) $request->get_param( 's' ) ) );
		$per_page = $this->per_page->choose( absint( $request->get_param( 'per_page' ) ) );
		$list     = $this->products->list( $term, max( 1, absint( $request->get_param( 'paged' ) ) ), $per_page );

		return new WP_REST_Response(
			array(
				'data' => array(
					'html'     => $this->template->fetch(
						'admin/partials/product-list',
						array(
							'products' => $list,
							'term'     => $term->text(),
							'url'      => DashboardUrl::to( $term->text(), 1, $per_page ),
						)
					),
					'term'     => $term->text(),
					'page'     => $list->current(),
					'per_page' => $per_page,
					'total'    => $list->total,
					'url'      => DashboardUrl::to( $term->text(), $list->current(), $per_page ),
				),
			)
		);
	}
}
