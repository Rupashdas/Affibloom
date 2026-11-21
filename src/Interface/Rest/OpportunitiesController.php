<?php
/**
 * The Opportunities screens' routes.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Application\Opportunities\IntroBoxes;
use Affibloom\Application\Opportunities\OpportunityService;
use Affibloom\Domain\Opportunity\OpportunityQuery;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Interface\Admin\OpportunitiesScreen;
use Affibloom\Interface\Template;
use Affibloom\Module;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * F22's routes (D3, D8, D20; Task O1): `GET /opportunities/links` answers the list drawn by the partial the page printed,
 * `{ html, term, page, orderby, order, total, url }`; `POST /opportunities/intros/{box}` closes an intro box for good.
 */
final class OpportunitiesController implements Module {

	/**
	 * Builds the controller.
	 *
	 * @param OpportunityService $opportunities The lists.
	 * @param IntroBoxes         $intros        The intro boxes.
	 * @param Template           $template      The list's template.
	 */
	public function __construct( private OpportunityService $opportunities, private IntroBoxes $intros, private Template $template ) {}

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
			'/opportunities/links',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'links' ),
				'permission_callback' => $can,
				'args'                => array(
					'search'  => array(
						'type'    => 'string',
						'default' => '',
					),
					'page'    => array(
						'type'    => 'integer',
						'minimum' => 1,
						'default' => 1,
					),
					'orderby' => array(
						'type'    => 'string',
						'enum'    => array_merge( array( '' ), OpportunityQuery::COLUMNS ),
						'default' => '',
					),
					'order'   => array(
						'type'    => 'string',
						'enum'    => array( '', 'asc', 'desc' ),
						'default' => '',
					),
					'product' => array(
						'type'    => 'integer',
						'minimum' => 0,
						'default' => 0,
					),
				),
			)
		);
		register_rest_route(
			ProductsController::NAMESPACE,
			'/opportunities/intros/(?P<box>[a-z]+)',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'hide_intro' ),
				'permission_callback' => $can,
				'args'                => array(
					'box' => array(
						'type' => 'string',
						'enum' => IntroBoxes::BOXES,
					),
				),
			)
		);
	}

	/**
	 * GET: one page of Link Opportunities.
	 *
	 * @param WP_REST_Request $request Request with "search", "page", "orderby", "order" and "product".
	 * @phpstan-param WP_REST_Request<array{search: string, page: int, orderby: string, order: string, product: int}> $request
	 */
	public function links( WP_REST_Request $request ): WP_REST_Response {
		$query = OpportunitiesScreen::query( sanitize_text_field( (string) $request['search'] ), (string) $request['orderby'], (string) $request['order'], (int) $request['page'], (int) $request['product'] );
		$list  = $this->opportunities->links( $query );

		return new WP_REST_Response(
			array(
				'data' => array(
					'html'    => $this->template->fetch(
						'admin/partials/opportunity-links-list',
						array(
							'list'  => $list,
							'query' => $query,
							'url'   => OpportunitiesScreen::links_url( $query, 1 ),
						)
					),
					'term'    => $query->search,
					'page'    => $list->current(),
					'orderby' => $query->orderby,
					'order'   => $query->order,
					'total'   => $list->total,
					'url'     => OpportunitiesScreen::links_url( $query, $list->current() ),
				),
			)
		);
	}

	/**
	 * POST: closes an intro box for good (D8).
	 *
	 * @param WP_REST_Request $request Request with "box".
	 * @phpstan-param WP_REST_Request<array{box: string}> $request
	 */
	public function hide_intro( WP_REST_Request $request ): WP_REST_Response {
		$this->intros->hide( (string) $request['box'] );

		return new WP_REST_Response( null, 204 );
	}
}
