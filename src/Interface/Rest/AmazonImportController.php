<?php
/**
 * The Amazon import's status routes.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Application\Monetize\AmazonImportStatusService;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Interface\Template;
use Affibloom\Module;
use WP_REST_Response;
use WP_REST_Server;

/**
 * F35 R29, R35, D19 (Task AM4): `GET /amazon-import`, the status payload (R29) with the status block drawn as the page
 * draws it (`html`), which amazon-import-status.js polls every 15 seconds; `POST /amazon-import/dismiss`, which hides
 * the notice for this person until the counts change. Both for people who manage products.
 */
final class AmazonImportController implements Module {

	/**
	 * Builds the controller.
	 *
	 * @param AmazonImportStatusService $status   The import's status.
	 * @param Template                  $template The status block's template.
	 */
	public function __construct( private AmazonImportStatusService $status, private Template $template ) {}

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
		$can = static fn(): bool => current_user_can( Capabilities::PRODUCTS );
		register_rest_route(
			ProductsController::NAMESPACE,
			'/amazon-import',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'show' ),
				'permission_callback' => $can,
			)
		);
		register_rest_route(
			ProductsController::NAMESPACE,
			'/amazon-import/dismiss',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'dismiss' ),
				'permission_callback' => $can,
			)
		);
	}

	/**
	 * GET /amazon-import: the payload and the status block.
	 */
	public function show(): WP_REST_Response {
		$status = $this->status->status();

		return new WP_REST_Response(
			array(
				'data' => $status->to_array() + array(
					'html' => $this->template->fetch( 'admin/partials/amazon-import-status', array( 'status' => $status ) ),
				),
			)
		);
	}

	/**
	 * POST /amazon-import/dismiss: hidden for this person until the counts change.
	 */
	public function dismiss(): WP_REST_Response {
		$this->status->dismiss( get_current_user_id() );

		return new WP_REST_Response( null, 204 );
	}
}
