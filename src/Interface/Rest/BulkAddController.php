<?php
/**
 * REST routes for bulk add: affibloom/v1/products/bulk.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Application\Products\BulkAddRunning;
use Affibloom\Application\Products\BulkAddService;
use Affibloom\Domain\InvalidField;
use Affibloom\Interface\Admin\AdminMenu;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Interface\Messages;
use Affibloom\Module;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST routes for bulk add (F18 section 8): POST starts a run, GET reports it. Both check the REST nonce and the
 * capability; Lasso's progress endpoint checks neither.
 */
final class BulkAddController implements Module {

	/**
	 * Builds the controller.
	 *
	 * @param BulkAddService $bulk Bulk add.
	 */
	public function __construct( private BulkAddService $bulk ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
	}

	/**
	 * Registers the routes.
	 */
	public function routes(): void {
		register_rest_route(
			ProductsController::NAMESPACE,
			'/products/bulk',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'start' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => array(
						'urls' => array(
							'type'     => 'array',
							'required' => true,
							'items'    => array( 'type' => 'string' ),
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'progress' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
			)
		);
	}

	/**
	 * Only people who manage products may add them (decision 1).
	 */
	public function can_manage(): bool {
		return current_user_can( Capabilities::PRODUCTS );
	}

	/**
	 * POST /products/bulk: starts a run. 202, because the links are added afterwards.
	 *
	 * @param WP_REST_Request $request Request with "urls".
	 * @phpstan-param WP_REST_Request<array{urls: list<string>}> $request
	 */
	public function start( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		try {
			// The schema's sanitising (rest_sanitize_array) already gives a list of strings.
			$total = $this->bulk->start( $request['urls'] );
		} catch ( InvalidField $error ) {
			return FieldError::from( $error );
		} catch ( BulkAddRunning $running ) {
			return new WP_Error( 'affibloom_bulk_running', Messages::for_reason( 'bulk_running' ), array( 'status' => 409 ) );
		}

		return new WP_REST_Response( array( 'data' => array( 'total' => $total ) ), 202 );
	}

	/**
	 * GET /products/bulk: how the run stands, and where to go when it is done (Lasso reloads the Dashboard; until
	 * Task 13 lists the products, the Dashboard says the run finished).
	 */
	public function progress(): WP_REST_Response {
		$data                = $this->bulk->progress();
		$data['redirect_to'] = add_query_arg( 'affibloom_notice', 'bulk-added', admin_url( 'admin.php?page=' . AdminMenu::SLUG ) );

		return new WP_REST_Response( array( 'data' => $data ) );
	}
}
