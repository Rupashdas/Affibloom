<?php
/**
 * REST routes for products: affibloom/v1/products.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Application\Products\DuplicateLink;
use Affibloom\Application\Products\ProductNotFound;
use Affibloom\Application\Products\ProductService;
use Affibloom\Application\Products\RepointRefused;
use Affibloom\Application\Products\ValidationFailed;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Interface\Admin\AdminMenu;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Interface\Admin\ProductScreen;
use Affibloom\Interface\Messages;
use Affibloom\Module;
use RuntimeException;
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

		// Save Changes on the product page (Task 15A). Only the fields sent change.
		// Delete This Link on the product page (Task 17A): for good, as Lasso's lasso_delete_post.
		register_rest_route(
			self::NAMESPACE,
			'/products/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => $this->field_args(),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
			)
		);

		// "This Product Already Exists" → Confirm (Task 15C).
		register_rest_route(
			self::NAMESPACE,
			'/products/(?P<id>\d+)/repoint',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'repoint' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => array(
						'to'  => array(
							'type'     => 'integer',
							'required' => true,
						),
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
	 * The product page's fields and their types; WordPress refuses a wrong type (400), ProductInput and the service
	 * apply Lasso's rules to the rest.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function field_args(): array {
		$args = array();
		foreach ( array( 'name', 'url', 'button_text', 'theme', 'image_url', 'badge', 'price', 'description', 'disclosure' ) as $field ) {
			$args[ $field ] = array( 'type' => 'string' );
		}
		foreach ( array( 'new_tab', 'nofollow', 'sponsored', 'show_disclosure', 'prime', 'show_price' ) as $field ) {
			$args[ $field ] = array( 'type' => 'boolean' );
		}
		$args['image_id']         = array(
			'type'    => 'integer',
			'minimum' => 0,
		);
		$args['primary_position'] = array(
			'type'    => 'integer',
			'minimum' => 0,
		);
		$args['buttons']          = array(
			'type'  => 'array',
			'items' => array(
				'type'       => 'object',
				'properties' => array(
					'url'      => array( 'type' => 'string' ),
					'text'     => array( 'type' => 'string' ),
					'new_tab'  => array( 'type' => 'boolean' ),
					'nofollow' => array( 'type' => 'boolean' ),
				),
			),
		);

		return $args;
	}

	/**
	 * PATCH /products/{id}: stores the fields sent and answers with the product as stored (S15).
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response|WP_Error 200 with the product; 404 for no such product; 422 with a message per field.
	 */
	public function update( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$input = array_intersect_key( $request->get_params(), $this->field_args() );

		try {
			$product = $this->products->update( (int) $request['id'], $input );
		} catch ( ProductNotFound $missing ) {
			return new WP_Error( 'affibloom_not_found', __( 'This product does not exist.', 'affibloom' ), array( 'status' => 404 ) );
		} catch ( DuplicateLink $duplicate ) {
			// Lasso's repoint_offer: nothing saved; both products for the dialog (url-save.php's second panel).
			$current = $this->products->get( (int) $request['id'] );
			return new WP_Error(
				'affibloom_duplicate',
				__( 'This Product Already Exists', 'affibloom' ),
				array(
					'status' => 409,
					'from'   => null === $current ? null : self::offer( $current ),
					'to'     => self::offer( $duplicate->other ),
				)
			);
		} catch ( ValidationFailed $refused ) {
			$fields = array_map( array( Messages::class, 'for_reason' ), $refused->errors );

			return new WP_Error(
				'affibloom_invalid',
				(string) reset( $fields ),
				array(
					'status' => 422,
					'fields' => $fields,
				)
			);
		}

		return new WP_REST_Response( array( 'data' => $this->product_resource->full( $product ) ) );
	}

	/**
	 * DELETE /products/{id}: deletes the product for good (F1 requirements 11, X3, D33, D39). Anything that is not a
	 * published product is not found (Lasso answers "Invalid post." with 400; 404 is this API's word for it).
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response|WP_Error 200 { id, deleted: true }; 404 for no such product; 500 when WordPress refuses.
	 */
	public function delete( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$id = (int) $request['id'];
		try {
			$this->products->delete( $id );
		} catch ( ProductNotFound $missing ) {
			return new WP_Error( 'affibloom_not_found', __( 'This product does not exist.', 'affibloom' ), array( 'status' => 404 ) );
		} catch ( RuntimeException $refused ) {
			return new WP_Error( 'affibloom_not_deleted', __( 'Could not delete. Please try again.', 'affibloom' ), array( 'status' => 500 ) );
		}

		return new WP_REST_Response(
			array(
				'data' => array(
					'id'      => $id,
					'deleted' => true,
				),
			)
		);
	}

	/**
	 * POST /products/{id}/repoint: retires this product in favour of the one that has the link, and says where to go.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response|WP_Error 200 with redirect_to; 400 with Lasso's message when the repoint no longer fits.
	 */
	public function repoint( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		try {
			$kept = $this->products->repoint( (int) $request['id'], (int) $request['to'], (string) $request['url'] );
		} catch ( RepointRefused $refused ) {
			return new WP_Error( 'affibloom_repoint_refused', Messages::for_reason( $refused->reason ), array( 'status' => 400 ) );
		}

		return new WP_REST_Response(
			array( 'data' => array( 'redirect_to' => add_query_arg( 'affibloom_notice', 'repointed', ProductScreen::url( (int) $kept->id ) ) ) )
		);
	}

	/**
	 * One side of "This Product Already Exists": its name, its link and its page (Lasso's repoint_from_* / repoint_to_*).
	 *
	 * @param Product $product A stored product.
	 * @return array{id: int, name: string, url: string, page: string}
	 */
	private static function offer( Product $product ): array {
		return array(
			'id'   => (int) $product->id,
			'name' => $product->name,
			'url'  => $product->url->value(),
			'page' => ProductScreen::url( (int) $product->id ),
		);
	}

	/**
	 * Only people who manage products may add or change them (decision 1; Lasso's endpoint allowed edit_posts).
	 */
	public function can_manage(): bool {
		return current_user_can( Capabilities::PRODUCTS );
	}

	/**
	 * POST /products: "Add A New Link" with one link. A link the site already has makes no product: 200 with
	 * "duplicate", as Lasso sends the editor to the existing one.
	 *
	 * @param WP_REST_Request $request Request with "url".
	 * @phpstan-param WP_REST_Request<array{url: string}> $request
	 */
	public function create( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		try {
			$added = $this->products->create_from_url( (string) $request['url'] );
		} catch ( InvalidField $error ) {
			return FieldError::from( $error );
		}

		$data = $this->product_resource->summary( $added->product );
		if ( $added->already_existed ) {
			$data['duplicate'] = true;
		}
		// Where Lasso goes after an add (url-add.php, the success handler), with no message of its own:
		// - an Amazon link, new or found, reloads the Dashboard (its is_amazon branch runs before the duplicate one);
		// - any other link opens the product's page (Task 14A), with Lasso's warning when it already existed, which
		// Lasso asks for with ?is_duplicate=true (url-details.php 2597-2600).
		if ( ProductType::Amazon === $added->product->type ) {
			$data['redirect_to'] = admin_url( 'admin.php?page=' . AdminMenu::SLUG );
		} else {
			$page                = ProductScreen::url( (int) $added->product->id );
			$data['redirect_to'] = $added->already_existed ? add_query_arg( 'is_duplicate', '1', $page ) : $page;
		}

		return new WP_REST_Response( array( 'data' => $data ), $added->already_existed ? 200 : 201 );
	}
}
