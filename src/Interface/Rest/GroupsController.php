<?php
/**
 * REST routes for groups: affibloom/v1/groups (F23, Task G1).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

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
 * REST routes for groups. G1 has the list, redrawn in place by the Groups screen's script (D13) with the markup the page
 * printed, and a new group; G2-G5 add the rest.
 */
final class GroupsController implements Module {

	/**
	 * Builds the controller.
	 *
	 * @param GroupService $groups   The groups.
	 * @param Template     $template Renders the list partial.
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
		register_rest_route(
			ProductsController::NAMESPACE,
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
					'args'                => array(
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

		return new WP_REST_Response(
			array(
				'data' => array(
					'id'          => $group->id,
					'redirect_to' => add_query_arg( 'affibloom_notice', 'group-saved', GroupScreen::url( $group->id, 'details' ) ),
				),
			),
			201
		);
	}
}
