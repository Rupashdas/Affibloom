<?php
/**
 * The link index's status and rebuild routes.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Application\LinkIndex\LinkIndexRebuild;
use Affibloom\Application\LinkIndex\LinkIndexStatus;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Interface\Admin\LinkIndexText;
use Affibloom\Module;
use WP_Error;
use WP_REST_Response;
use WP_REST_Server;

/**
 * `GET /affibloom/v1/link-index` (the Link Index card's figures, polled every 5 s; F21 R57) and
 * `POST /affibloom/v1/link-index/rebuild` (Rebuild Database; R52, D17): 202 when a background rebuild starts, 409
 * `affibloom_rebuild_running` while one runs. Both need the settings capability, as Lasso's `lasso_get_stats`.
 */
final class LinkIndexController implements Module {

	/**
	 * Builds the controller.
	 *
	 * @param LinkIndexStatus  $status  The figures.
	 * @param LinkIndexRebuild $rebuild Starts a rebuild.
	 */
	public function __construct( private LinkIndexStatus $status, private LinkIndexRebuild $rebuild ) {}

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
			'/link-index',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'show' ),
				'permission_callback' => array( $this, 'can_see' ),
			)
		);
		register_rest_route(
			ProductsController::NAMESPACE,
			'/link-index/rebuild',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'rebuild' ),
				'permission_callback' => array( $this, 'can_see' ),
			)
		);
	}

	/**
	 * Whether the user may see and rebuild the index.
	 */
	public function can_see(): bool {
		return current_user_can( Capabilities::SETTINGS );
	}

	/**
	 * The figures.
	 */
	public function show(): WP_REST_Response {
		return new WP_REST_Response( LinkIndexText::of( $this->status->figures( time() ) ), 200 );
	}

	/**
	 * Starts a rebuild: "Rebuild started.", or 409 "A rebuild is already running." (D17).
	 */
	public function rebuild(): WP_REST_Response|WP_Error {
		if ( ! $this->rebuild->start() ) {
			return new WP_Error( 'affibloom_rebuild_running', __( 'A rebuild is already running.', 'affibloom' ), array( 'status' => 409 ) );
		}

		return new WP_REST_Response(
			array(
				'message' => __( 'Rebuild started.', 'affibloom' ),
				'status'  => LinkIndexText::of( $this->status->figures( time() ) ),
			),
			202
		);
	}
}
