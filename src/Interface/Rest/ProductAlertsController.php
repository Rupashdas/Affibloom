<?php
/**
 * Closing a product page's issue bar.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Application\LinkHealth\AlertDismissals;
use Affibloom\Application\LinkHealth\LinkIssueQuery;
use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Module;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * POST /affibloom/v1/products/{id}/alerts/dismiss { type } (F11 R29, D17; Lasso's lasso_dismiss_notification): the
 * product page's broken or out-of-stock bar stays closed for 7 days. People who manage products and may edit this one;
 * 400 affibloom_invalid_alert_type for another type (Lasso's "Invalid post_id or type"), 404 for a product that is not
 * there.
 */
final class ProductAlertsController implements Module {

	/**
	 * Builds the controller.
	 *
	 * @param LinkIssueQuery    $alerts   Closes the bar.
	 * @param ProductRepository $products Whether the product exists.
	 */
	public function __construct( private LinkIssueQuery $alerts, private ProductRepository $products ) {}

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
			'/products/(?P<id>\d+)/alerts/dismiss',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'dismiss' ),
					'permission_callback' => static fn( WP_REST_Request $request ): bool => current_user_can( Capabilities::PRODUCTS ) && current_user_can( 'edit_post', (int) $request['id'] ),
					'args'                => array(
						'type' => array(
							'type'     => 'string',
							'required' => true,
						),
					),
				),
			)
		);
	}

	/**
	 * Closes the bar for 7 days.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request Request with id and type.
	 * @return WP_REST_Response|WP_Error
	 */
	public function dismiss( WP_REST_Request $request ) {
		$type = (string) $request->get_param( 'type' );
		if ( ! in_array( $type, array( AlertDismissals::BROKEN, AlertDismissals::OUT_OF_STOCK ), true ) ) {
			return new WP_Error( 'affibloom_invalid_alert_type', __( 'Invalid post_id or type', 'affibloom' ), array( 'status' => 400 ) );
		}
		$id = (int) $request['id'];
		if ( null === $this->products->get( $id ) ) {
			return new WP_Error( 'affibloom_product_not_found', __( 'Invalid post_id or type', 'affibloom' ), array( 'status' => 404 ) );
		}

		return new WP_REST_Response( array( 'data' => array( 'dismiss_until' => $this->alerts->dismiss( $id, $type ) ) ) );
	}
}
