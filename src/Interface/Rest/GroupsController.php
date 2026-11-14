<?php
/**
 * REST routes for groups: affibloom/v1/groups (F23, Tasks G1-G5).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Application\Groups\AlreadyInGroup;
use Affibloom\Application\Groups\GroupInUse;
use Affibloom\Application\Groups\GroupNotFound;
use Affibloom\Application\Groups\GroupService;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\SearchTerm;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Interface\Admin\GroupScreen;
use Affibloom\Interface\Admin\GroupsUrl;
use Affibloom\Interface\Template;
use Affibloom\Module;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST routes for groups: the list (redrawn in place with the markup the page printed, D13), a group's details, its
 * products and their order, and the Dashboard's bulk Add to Group.
 */
final class GroupsController implements Module {

	/**
	 * Builds the controller.
	 *
	 * @param GroupService $groups   The groups.
	 * @param Template     $template Renders the list and the member row.
	 */
	public function __construct(
		private GroupService $groups,
		private Template $template,
	) {}

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
		$ns     = ProductsController::NAMESPACE;
		$id     = array(
			'type'    => 'integer',
			'minimum' => 1,
		);
		$fields = array(
			'name'        => array(
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'description' => array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_textarea_field',
			),
		);
		$ids    = array(
			'type'     => 'array',
			'items'    => array(
				'type'    => 'integer',
				'minimum' => 1,
			),
			'required' => true,
		);

		register_rest_route(
			$ns,
			'/groups',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'list' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => array(
						'search' => array(
							'type'    => 'string',
							'default' => '',
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
					'args'                => $fields,
				),
			)
		);

		register_rest_route(
			$ns,
			'/groups/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => $fields,
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
			)
		);

		register_rest_route(
			$ns,
			'/groups/(?P<id>\d+)/products',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'add_product' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => array( 'product' => $id + array( 'required' => true ) ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'remove_products' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => array( 'ids' => $ids ),
				),
			)
		);

		register_rest_route(
			$ns,
			'/groups/(?P<id>\d+)/order',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'reorder' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => array( 'ids' => $ids ),
				),
			)
		);

		// The Dashboard's bulk Add to Group (R23): groups are appended, the products keep the ones they have. A value is a
		// group's ID or a new name; at most a page of products (50 is the largest page size).
		register_rest_route(
			$ns,
			'/products/groups',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'append' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => array(
						'ids'    => array(
							'type'     => 'array',
							'items'    => array(
								'type'    => 'integer',
								'minimum' => 1,
							),
							'minItems' => 1,
							'maxItems' => 50,
							'required' => true,
						),
						'groups' => array(
							'type'     => 'array',
							'minItems' => 1,
							'required' => true,
						),
					),
				),
			)
		);
	}

	/**
	 * Only people who manage products work with groups (F23 D2).
	 */
	public function can_manage(): bool {
		return current_user_can( Capabilities::PRODUCTS );
	}

	/**
	 * GET /groups: one page of the list, as the screen would print it, and the screen's own address for it.
	 *
	 * @param WP_REST_Request $request Request with "search" and "page".
	 * @phpstan-param WP_REST_Request<array{search: string, page: int}> $request
	 */
	public function list( WP_REST_Request $request ): WP_REST_Response {
		$term   = SearchTerm::from_input( sanitize_text_field( (string) $request['search'] ) );
		$groups = $this->groups->list( $term, max( 1, (int) $request['page'] ) );

		return new WP_REST_Response(
			array(
				'data' => array(
					'html'  => $this->template->fetch(
						'admin/partials/groups-list',
						array(
							'groups' => $groups,
							'term'   => $term->text(),
							'url'    => GroupsUrl::to( $term->text(), 1 ),
						)
					),
					'term'  => $term->text(),
					'page'  => $groups->current(),
					'total' => $groups->total,
					'url'   => GroupsUrl::to( $term->text(), $groups->current() ),
				),
			)
		);
	}

	/**
	 * POST /groups: a new group (Lasso's `lasso_store_category`). An empty name or one in use is a 422 with the
	 * sentence under Name; a good save answers with the address of the group's Details tab (D11).
	 *
	 * @param WP_REST_Request $request Request with "name" and "description".
	 * @phpstan-param WP_REST_Request<array{name: string, description: string}> $request
	 */
	public function create( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		try {
			$group = $this->groups->create( (string) $request['name'], (string) $request['description'] );
		} catch ( InvalidField $error ) {
			return FieldError::from( $error );
		}

		return new WP_REST_Response( array( 'data' => $this->saved( $group->id ) ), 201 );
	}

	/**
	 * PATCH /groups/{id}: the name and description; the slug is kept.
	 *
	 * @param WP_REST_Request $request Request with the ID, "name" and "description".
	 * @phpstan-param WP_REST_Request<array{id: string, name: string, description: string}> $request
	 */
	public function update( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		try {
			$group = $this->groups->update( (int) $request['id'], (string) $request['name'], (string) $request['description'] );
		} catch ( GroupNotFound $missing ) {
			return self::not_found();
		} catch ( InvalidField $error ) {
			return FieldError::from( $error );
		}

		return new WP_REST_Response( array( 'data' => $this->saved( $group->id ) ) );
	}

	/**
	 * DELETE /groups/{id}: a group with published products is refused (D4), the server's own check.
	 *
	 * @param WP_REST_Request $request Request with the ID.
	 * @phpstan-param WP_REST_Request<array{id: string}> $request
	 */
	public function delete( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		try {
			$this->groups->delete( (int) $request['id'] );
		} catch ( GroupNotFound $missing ) {
			return self::not_found();
		} catch ( GroupInUse $in_use ) {
			return new WP_Error(
				'affibloom_group_in_use',
				__( "You can't delete a Group if it has associated Links. Remove all Links using this Group first.", 'affibloom' ),
				array( 'status' => 409 )
			);
		}

		return new WP_REST_Response(
			array(
				'data' => array(
					'redirect_to' => add_query_arg( 'affibloom_notice', 'group-deleted', GroupsUrl::to( '', 1 ) ),
				),
			)
		);
	}

	/**
	 * POST /groups/{id}/products: puts a product in the group, last, and answers with its row (R20).
	 *
	 * @param WP_REST_Request $request Request with the group's ID and "product".
	 * @phpstan-param WP_REST_Request<array{id: string, product: int}> $request
	 */
	public function add_product( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		try {
			$product = $this->groups->add( (int) $request['id'], (int) $request['product'] );
		} catch ( GroupNotFound $missing ) {
			return self::not_found();
		} catch ( AlreadyInGroup $exists ) {
			return new WP_Error( 'affibloom_already_in_group', __( 'Link already exists.', 'affibloom' ), array( 'status' => 409 ) );
		}

		return new WP_REST_Response(
			array(
				'data' => array(
					'html'  => $this->template->fetch( 'admin/partials/group-member-row', array( 'product' => $product ) ),
					'count' => count( $this->groups->members( (int) $request['id'] ) ),
				),
			),
			201
		);
	}

	/**
	 * DELETE /groups/{id}/products?ids[]=…: takes products out of the group only (R18, D6).
	 *
	 * @param WP_REST_Request $request Request with the group's ID and "ids".
	 * @phpstan-param WP_REST_Request<array{id: string, ids: list<int>}> $request
	 */
	public function remove_products( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$ids = array_map( 'intval', (array) $request['ids'] );
		try {
			$this->groups->remove( (int) $request['id'], $ids );
		} catch ( GroupNotFound $missing ) {
			return self::not_found();
		}

		return new WP_REST_Response(
			array(
				'data' => array(
					'removed' => $ids,
					'count'   => count( $this->groups->members( (int) $request['id'] ) ),
				),
			)
		);
	}

	/**
	 * PUT /groups/{id}/order: the whole order, after a drop or a Move button (R19, D7).
	 *
	 * @param WP_REST_Request $request Request with the group's ID and "ids".
	 * @phpstan-param WP_REST_Request<array{id: string, ids: list<int>}> $request
	 */
	public function reorder( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		try {
			$this->groups->reorder( (int) $request['id'], array_map( 'intval', (array) $request['ids'] ) );
		} catch ( GroupNotFound $missing ) {
			return self::not_found();
		}

		return new WP_REST_Response( array( 'data' => array( 'saved' => true ) ) );
	}

	/**
	 * POST /products/groups: the Dashboard's bulk Add to Group (R23).
	 *
	 * @param WP_REST_Request $request Request with "ids" and "groups".
	 * @phpstan-param WP_REST_Request<array{ids: list<int>, groups: list<int|string>}> $request
	 */
	public function append( WP_REST_Request $request ): WP_REST_Response {
		$values = array();
		foreach ( (array) $request['groups'] as $value ) {
			$values[] = is_string( $value ) ? sanitize_text_field( $value ) : $value;
		}
		$updated = $this->groups->append( array_map( 'intval', (array) $request['ids'] ), $values );

		return new WP_REST_Response( array( 'data' => array( 'updated' => $updated ) ) );
	}

	/**
	 * The answer to a good save: the group and where its Details tab is, with Lasso's "saved" toast (D11).
	 *
	 * @param int $id The group's ID.
	 * @return array{id: int, redirect_to: string}
	 */
	private function saved( int $id ): array {
		return array(
			'id'          => $id,
			'redirect_to' => add_query_arg( 'affibloom_notice', 'group-saved', GroupScreen::url( $id, 'details' ) ),
		);
	}

	/**
	 * The 404 for a group that is not there.
	 */
	private static function not_found(): WP_Error {
		return new WP_Error( 'affibloom_not_found', __( 'This group does not exist.', 'affibloom' ), array( 'status' => 404 ) );
	}
}
