<?php
/**
 * REST routes for Settings: affibloom/v1/settings (Task 9D).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Application\Settings\LinkDefaultsService;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Module;
use InvalidArgumentException;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST routes for Settings. The one route so far is Lasso's "Override All Links": the Update Link Settings dialog
 * pushes a changed link default to every existing product at once, before and apart from Save (F9 D7, R48).
 */
final class SettingsRestController implements Module {

	/**
	 * Builds the controller.
	 *
	 * @param LinkDefaultsService $defaults Pushes a link default to every product.
	 */
	public function __construct( private LinkDefaultsService $defaults ) {}

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
			'/settings/link-defaults/apply',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'apply' ),
				'permission_callback' => array( $this, 'can_change' ),
				'args'                => array(
					'key'   => array(
						'type'     => 'string',
						'required' => true,
					),
					'value' => array(
						'type'     => 'boolean',
						'required' => true,
					),
				),
			)
		);
	}

	/**
	 * Only people who change settings may push one to every product.
	 */
	public function can_change(): bool {
		return current_user_can( Capabilities::SETTINGS );
	}

	/**
	 * POST /settings/link-defaults/apply: sets one switch on every product.
	 *
	 * @param WP_REST_Request $request Request with "key" and "value".
	 * @phpstan-param WP_REST_Request<array{key: string, value: bool}> $request
	 */
	public function apply( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		try {
			$count = $this->defaults->apply_to_all( (string) $request['key'], (bool) $request['value'] );
		} catch ( InvalidArgumentException $error ) {
			return new WP_Error( 'affibloom_unknown_default', $error->getMessage(), array( 'status' => 422 ) );
		}

		return new WP_REST_Response( array( 'data' => array( 'updated' => $count ) ) );
	}
}
