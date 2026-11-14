<?php
/**
 * REST routes for the custom field library: affibloom/v1/fields (F1, Task 14C).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Application\Fields\FieldsService;
use Affibloom\Domain\Fields\Field;
use Affibloom\Domain\InvalidField;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Interface\Admin\FieldsUrl;
use Affibloom\Interface\Template;
use Affibloom\Module;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST routes for the custom fields: the product page's "Add Field" dialog (the library, ten a page, searched by name,
 * and a new field) and the Fields list, redrawn in place by the Fields screen's script (Task FL1, F26 D15) with the markup
 * the page printed.
 */
final class FieldsController implements Module {

	/**
	 * Builds the controller.
	 *
	 * @param FieldsService $fields   Custom field use cases.
	 * @param Template      $template Renders the Fields list partial.
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
		register_rest_route(
			ProductsController::NAMESPACE,
			'/fields/list',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'list' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => array(
						'search' => array(
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'page'   => array(
							'type'    => 'integer',
							'minimum' => 1,
							'default' => 1,
						),
					),
				),
			)
		);

		register_rest_route(
			ProductsController::NAMESPACE,
			'/fields',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'library' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => array(
						'search' => array(
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'page'   => array(
							'type'    => 'integer',
							'minimum' => 1,
							'default' => 1,
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => array(
						'name'        => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'type'        => array(
							'type'     => 'string',
							'required' => true,
						),
						'description' => array(
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_textarea_field',
						),
					),
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
	 * GET /fields/list: one page of the Fields list, as the screen would print it, and the screen's own address for it.
	 *
	 * @param WP_REST_Request $request Request with "search" and "page".
	 * @phpstan-param WP_REST_Request<array{search: string, page: int}> $request
	 */
	public function list( WP_REST_Request $request ): WP_REST_Response {
		$term    = trim( (string) $request['search'] );
		$listing = $this->fields->list( $term, max( 1, (int) $request['page'] ) );

		return new WP_REST_Response(
			array(
				'data' => array(
					'html'  => $this->template->fetch(
						'admin/partials/fields-list',
						array(
							'listing' => $listing,
							'term'    => $term,
							'url'     => FieldsUrl::to( $term, 1 ),
						)
					),
					'term'  => $term,
					'page'  => $listing->page->page,
					'total' => $listing->page->total,
					'url'   => FieldsUrl::to( $term, $listing->page->page ),
				),
			)
		);
	}

	/**
	 * GET /fields: one page of the library.
	 *
	 * @param WP_REST_Request $request Request with "search" and "page".
	 * @phpstan-param WP_REST_Request<array{search: string, page: int}> $request
	 */
	public function library( WP_REST_Request $request ): WP_REST_Response {
		$page = $this->fields->library( (string) $request['search'], (int) $request['page'] );

		return new WP_REST_Response(
			array(
				'data' => array(
					'items' => array_map( array( self::class, 'field' ), $page->items ),
					'total' => $page->total,
					'page'  => $page->page,
					'pages' => $page->pages,
				),
			)
		);
	}

	/**
	 * POST /fields: a new field (Lasso's "Create Field"), added to no product yet.
	 *
	 * @param WP_REST_Request $request Request with "name", "type" and "description".
	 * @phpstan-param WP_REST_Request<array{name: string, type: string, description: string}> $request
	 */
	public function create( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		try {
			$field = $this->fields->create( (string) $request['name'], (string) $request['type'], (string) $request['description'] );
		} catch ( InvalidField $error ) {
			return FieldError::from( $error );
		}

		return new WP_REST_Response( array( 'data' => self::field( $field ) ), 201 );
	}

	/**
	 * A field as the dialog shows it.
	 *
	 * @param Field $field The field.
	 * @return array{id: int, name: string, type: string, type_label: string, description: string}
	 */
	public static function field( Field $field ): array {
		return array(
			'id'          => $field->id,
			'name'        => $field->name,
			'type'        => $field->type->value,
			'type_label'  => $field->type->label(),
			'description' => $field->description,
		);
	}
}
