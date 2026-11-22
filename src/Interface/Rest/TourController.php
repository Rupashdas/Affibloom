<?php
/**
 * Ends the Dashboard tour (Task ON8).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Application\Shell\TourState;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Module;
use WP_REST_Response;
use WP_REST_Server;

/**
 * POST /affibloom/v1/tour/done (F38 R62, AC12; Task ON8): Done or Skip ends the Dashboard tour for every user.
 */
final class TourController implements Module {

	/**
	 * Builds the controller.
	 *
	 * @param TourState $tour The tour's state.
	 */
	public function __construct( private TourState $tour ) {}

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
			'/tour/done',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'done' ),
				'permission_callback' => static fn(): bool => current_user_can( Capabilities::PRODUCTS ),
			)
		);
	}

	/**
	 * Ends the tour.
	 */
	public function done(): WP_REST_Response {
		$this->tour->finish();

		return new WP_REST_Response( null, 204 );
	}
}
