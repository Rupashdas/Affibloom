<?php
/**
 * The setup wizard's last step (Task ON7).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Application\Shell\SetupState;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Module;
use WP_REST_Response;
use WP_REST_Server;

/**
 * POST /affibloom/v1/setup/done (F38 R56, AC11; Task ON7): Go to Dashboard ends the wizard; it never opens by itself
 * again.
 */
final class SetupController implements Module {

	/**
	 * Builds the controller.
	 *
	 * @param SetupState $state The wizard's state.
	 */
	public function __construct( private SetupState $state ) {}

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
			'/setup/done',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'done' ),
				'permission_callback' => static fn(): bool => current_user_can( Capabilities::SETTINGS ),
			)
		);
	}

	/**
	 * Ends the wizard.
	 */
	public function done(): WP_REST_Response {
		$this->state->finish();

		return new WP_REST_Response( null, 204 );
	}
}
