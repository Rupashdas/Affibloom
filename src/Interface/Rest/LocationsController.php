<?php
/**
 * A product's Locations tab, redrawn in place.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Application\Locations\LocationService;
use Affibloom\Application\Products\ProductService;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Interface\Admin\ProductScreen;
use Affibloom\Interface\Template;
use Affibloom\Module;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * GET /affibloom/v1/products/{id}/locations?page=&order=&search= (F12 D22; Task 36B): the tab's list drawn by the
 * partial the page printed, `{ html, term, page, order, total, url }`, as the Groups and Fields lists answer (F23 D13).
 * A product that is gone is a 404 (edge case 3).
 */
final class LocationsController implements Module {

	/**
	 * Builds the controller.
	 *
	 * @param ProductService  $products  The products.
	 * @param LocationService $locations Their locations.
	 * @param Template        $template  The list's template.
	 */
	public function __construct( private ProductService $products, private LocationService $locations, private Template $template ) {}

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
			'/products/(?P<id>\d+)/locations',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'list' ),
					'permission_callback' => static fn(): bool => current_user_can( Capabilities::PRODUCTS ),
					'args'                => array(
						'id'     => array(
							'type'    => 'integer',
							'minimum' => 1,
						),
						'page'   => array(
							'type'    => 'integer',
							'minimum' => 1,
							'default' => 1,
						),
						'order'  => array(
							'type'    => 'string',
							'enum'    => array( '', 'asc', 'desc' ),
							'default' => '',
						),
						'search' => array(
							'type'    => 'string',
							'default' => '',
						),
					),
				),
			)
		);
	}

	/**
	 * GET: one page of the tab.
	 *
	 * @param WP_REST_Request $request Request with "id", "page", "order" and "search".
	 * @phpstan-param WP_REST_Request<array{id: int, page: int, order: string, search: string}> $request
	 */
	public function list( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$product = $this->products->get( (int) $request['id'] );
		if ( null === $product ) {
			return new WP_Error( 'affibloom_product_not_found', __( 'This product does not exist.', 'affibloom' ), array( 'status' => 404 ) );
		}
		$id    = (int) $product->id;
		$term  = trim( sanitize_text_field( (string) $request['search'] ) );
		$order = (string) $request['order'];
		$page  = $this->locations->page( $id, $term, $order, (int) $request['page'] );

		return new WP_REST_Response(
			array(
				'data' => array(
					'html'  => $this->template->fetch(
						'admin/partials/locations-list',
						array(
							'locations' => $page,
							'clicks'    => $this->locations->clicks( $page, time() ),
							'term'      => $term,
							'order'     => $order,
							'url'       => ProductScreen::locations_url( $id, $term, 1, $order ),
						)
					),
					'term'  => $term,
					'page'  => $page->current(),
					'order' => $order,
					'total' => $page->total,
					'url'   => ProductScreen::locations_url( $id, $term, $page->current(), $order ),
				),
			)
		);
	}
}
