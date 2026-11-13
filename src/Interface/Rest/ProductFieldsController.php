<?php
/**
 * REST routes for a product's custom fields: affibloom/v1/products/{id}/fields (F1, Task 14C).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Application\Fields\FieldsService;
use Affibloom\Domain\Fields\ProductField;
use Affibloom\Domain\Fields\ProductFields;
use Affibloom\Domain\InvalidField;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Interface\Template;
use Affibloom\Module;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST routes for a product's custom fields. Each change is stored as it is made, as Lasso's page does (B6, D3):
 * adding and removing answer with the rows drawn again; saving the order, values and toggles answers with the IDs only,
 * so the rows (and the focus in them) stay as they are.
 */
final class ProductFieldsController implements Module {

	/**
	 * Builds the controller.
	 *
	 * @param FieldsService $fields   Custom field use cases.
	 * @param Template      $template The rows' template.
	 */
	public function __construct( private FieldsService $fields, private Template $template ) {}

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
		$product = array(
			'id' => array(
				'type'    => 'integer',
				'minimum' => 1,
			),
		);

		register_rest_route(
			ProductsController::NAMESPACE,
			'/products/(?P<id>\d+)/fields',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'attach' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => $product + array(
						'field' => array(
							'type'     => 'integer',
							'required' => true,
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => $product + array(
						'rows' => array(
							'type'     => 'array',
							'required' => true,
							'items'    => array(
								'type'       => 'object',
								'properties' => array(
									'field'     => array( 'type' => 'integer' ),
									'value'     => array( 'type' => 'string' ),
									'visible'   => array( 'type' => 'boolean' ),
									'show_name' => array( 'type' => 'boolean' ),
								),
							),
						),
					),
				),
			)
		);

		register_rest_route(
			ProductsController::NAMESPACE,
			'/products/(?P<id>\d+)/fields/(?P<field>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'detach' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => $product,
				),
			)
		);
	}

	/**
	 * Only people who manage products change their fields (decision 1).
	 */
	public function can_manage(): bool {
		return current_user_can( Capabilities::PRODUCTS );
	}

	/**
	 * POST /products/{id}/fields: adds a field (Lasso's lasso_add_field_to_page).
	 *
	 * @param WP_REST_Request $request Request with "id" and "field".
	 * @phpstan-param WP_REST_Request<array{id: int, field: int}> $request
	 */
	public function attach( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		try {
			return $this->rows( (int) $request['id'], $this->fields->attach( (int) $request['id'], (int) $request['field'] ) );
		} catch ( InvalidField $error ) {
			return FieldError::from( $error );
		}
	}

	/**
	 * PUT /products/{id}/fields: the order, values and toggles (Lasso's lasso_save_field_positions). A value is cleaned
	 * as its kind needs: several lines for long text and lists, one line otherwise.
	 *
	 * @param WP_REST_Request $request Request with "id" and "rows".
	 * @phpstan-param WP_REST_Request<array{id: int, rows: list<array{field?: int, value?: string, visible?: bool, show_name?: bool}>}> $request
	 */
	public function update( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$library = $this->fields->all();
		$rows    = array();
		foreach ( (array) $request['rows'] as $row ) {
			$field     = $library->find( (int) ( $row['field'] ?? 0 ) );
			$multiline = null !== $field && $field->type->is_multiline();
			$value     = (string) ( $row['value'] ?? '' );
			$rows[]    = new ProductField(
				(int) ( $row['field'] ?? 0 ),
				$multiline ? sanitize_textarea_field( $value ) : sanitize_text_field( $value ),
				(bool) ( $row['visible'] ?? true ),
				(bool) ( $row['show_name'] ?? false ),
			);
		}

		$fields = $this->fields->update( (int) $request['id'], $rows );
		if ( null === $fields ) {
			return self::missing();
		}

		return new WP_REST_Response( array( 'data' => array( 'fields' => $fields->to_array() ) ) );
	}

	/**
	 * DELETE /products/{id}/fields/{field}: removes a field (Lasso's lasso_remove_field_from_page).
	 *
	 * @param WP_REST_Request $request Request with "id" and "field".
	 * @phpstan-param WP_REST_Request<array{id: int, field: string}> $request
	 */
	public function detach( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		return $this->rows( (int) $request['id'], $this->fields->detach( (int) $request['id'], (int) $request['field'] ) );
	}

	/**
	 * The rows drawn again, or 404 when the product does not exist.
	 *
	 * @param int                $product The product's ID.
	 * @param ProductFields|null $fields  Its fields, or null.
	 */
	private function rows( int $product, ?ProductFields $fields ): WP_REST_Response|WP_Error {
		if ( null === $fields ) {
			return self::missing();
		}

		return new WP_REST_Response(
			array(
				'data' => array(
					'html' => $this->template->fetch(
						'admin/partials/product-fields',
						array(
							'product' => $product,
							'fields'  => $fields,
							'library' => $this->fields->all(),
						)
					),
				),
			)
		);
	}

	/**
	 * 404 for a product that is not a published product.
	 */
	private static function missing(): WP_Error {
		return new WP_Error( 'affibloom_not_found', __( 'This product does not exist.', 'affibloom' ), array( 'status' => 404 ) );
	}
}
