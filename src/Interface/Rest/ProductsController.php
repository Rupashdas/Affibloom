<?php
/**
 * REST routes for products: affibloom/v1/products.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Application\Products\ProductService;
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
 * REST routes for products: affibloom/v1/products.
 */
final class ProductsController implements Module {

	public const NAMESPACE = 'affibloom/v1';

	/**
	 * Builds the controller.
	 *
	 * @param ProductService  $products         Product use cases.
	 * @param ProductResource $product_resource Products as arrays.
	 */
	public function __construct( private ProductService $products, private ProductResource $product_resource ) {}

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
			self::NAMESPACE,
			'/products',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => array(
						// Kept exactly as pasted: no sanitising and no length cap (Lasso has none); the domain validates it.
						'url' => array(
							'type'     => 'string',
							'required' => true,
						),
					),
				),
			)
		);
	}

	/**
	 * Only people who manage products may add them (decision 1; Lasso's endpoint allowed edit_posts).
	 */
	public function can_manage(): bool {
		return current_user_can( Capabilities::PRODUCTS );
	}

	/**
	 * POST /products: "Add A New Link" with one link.
	 *
	 * @param WP_REST_Request $request Request with "url".
	 * @phpstan-param WP_REST_Request<array{url: string}> $request
	 */
	public function create( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		try {
			$product = $this->products->create_from_url( (string) $request['url'] );
		} catch ( InvalidField $error ) {
			$message = Messages::for_reason( $error->reason );
			return new WP_Error(
				'affibloom_invalid_field',
				$message,
				array(
					'status' => 422,
					'fields' => array( $error->field => $message ),
				)
			);
		}

		$data = $this->product_resource->summary( $product );
		// Lasso opens the new product's page; that page is Task 14, so until then the Dashboard says it was added.
		$data['redirect_to'] = add_query_arg( 'affibloom_notice', 'link-added', admin_url( 'admin.php?page=' . AdminMenu::SLUG ) );

		return new WP_REST_Response( array( 'data' => $data ), 201 );
	}
}
