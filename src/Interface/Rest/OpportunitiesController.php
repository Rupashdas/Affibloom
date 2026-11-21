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
use Affibloom\Interface\Admin\ProductScreen;
use Affibloom\Interface\Template;
use Affibloom\Module;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * F22's routes (D3, D8, D20; Tasks O1, O2): `GET /opportunities/links`, `/domains` and `/domains/{domain}/links` answer
 * the list drawn by the partial the page printed, `{ html, term, page, orderby, order, total, url }`;
 * `POST /opportunities/intros/{box}` closes an intro box for good.
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
		$can   = static fn(): bool => current_user_can( Capabilities::PRODUCTS );
		$lists = array(
			'/opportunities/links'                     => OpportunityQuery::LINKS,
			'/opportunities/domains'                   => OpportunityQuery::DOMAINS,
			// A domain as the index stores it: lower case letters, digits, dots and hyphens.
			'/opportunities/domains/(?P<domain>[a-z0-9.-]+)/links' => OpportunityQuery::DOMAIN_LINKS,
			'/opportunities/content'                   => OpportunityQuery::CONTENT,
			// A post's links; 0 lists every published post's (R31).
			'/opportunities/posts/(?P<post>\d+)/links' => OpportunityQuery::POST_LINKS,
		);
		foreach ( $lists as $route => $kind ) {
			register_rest_route(
				ProductsController::NAMESPACE,
				$route,
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => fn( WP_REST_Request $request ): WP_REST_Response => $this->list( $kind, $request ),
					'permission_callback' => $can,
					'args'                => self::list_args( $kind ),
				)
			);
		}
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
	 * A list's arguments: the search, the page, its own sort allow-list (any other value is a 400), and the product
	 * filter of Link Opportunities.
	 *
	 * @param string $kind Which list.
	 * @return array<string, array<string, mixed>>
	 */
	private static function list_args( string $kind ): array {
		$args = array(
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
				'enum'    => array_merge( array( '' ), array_keys( OpportunityQuery::COLUMNS[ $kind ] ) ),
				'default' => '',
			),
			'order'   => array(
				'type'    => 'string',
				'enum'    => array( '', 'asc', 'desc' ),
				'default' => '',
			),
		);
		if ( OpportunityQuery::LINKS === $kind ) {
			$args['product'] = array(
				'type'    => 'integer',
				'minimum' => 0,
				'default' => 0,
			);
			// The product page's tab (R35, Task O5): its rows without the filter links, its addresses the page's.
			$args['context'] = array(
				'type'    => 'string',
				'enum'    => array( '', 'product' ),
				'default' => '',
			);
		}
		// Content's filter bar (R25); values outside Lasso's lists are its defaults (ContentFilter).
		if ( OpportunityQuery::CONTENT === $kind ) {
			$args += array(
				'show'         => array(
					'type'    => 'string',
					'default' => '',
				),
				'content_type' => array(
					'type'    => 'string',
					'default' => '',
				),
				'category'     => array(
					'type'    => 'integer',
					'minimum' => 0,
					'default' => 0,
				),
				'min_clicks'   => array(
					'type'    => 'integer',
					'minimum' => 0,
					'default' => 0,
				),
			);
		}

		return $args;
	}

	/**
	 * GET: one page of a list, drawn by the partial the page printed.
	 *
	 * @param string          $kind    Which list.
	 * @param WP_REST_Request $request Request with "search", "page", "orderby", "order", and "product" or "domain".
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 */
	public function list( string $kind, WP_REST_Request $request ): WP_REST_Response {
		$query      = OpportunitiesScreen::query( $kind, sanitize_text_field( (string) $request['search'] ), (string) $request['orderby'], (string) $request['order'], (int) $request['page'], (int) $request['product'] );
		$domain     = strtolower( (string) $request['domain'] );
		$on_product = 'product' === $request['context'] && $query->product > 0;
		$filter     = OpportunitiesScreen::filter( (string) $request['show'], (string) $request['content_type'], (int) $request['category'], (int) $request['min_clicks'] );
		$page       = match ( $kind ) {
			OpportunityQuery::DOMAINS      => $this->opportunities->domains( $query ),
			OpportunityQuery::DOMAIN_LINKS => $this->opportunities->domain_links( $domain, $query ),
			OpportunityQuery::CONTENT      => $this->opportunities->content( $query, $filter, time() ),
			OpportunityQuery::POST_LINKS   => $this->opportunities->post_links( (int) $request['post'], $query ),
			default                       => $this->opportunities->links( $query ),
		};
		$partials = array(
			OpportunityQuery::LINKS        => 'admin/partials/opportunity-links-list',
			OpportunityQuery::DOMAINS      => 'admin/partials/opportunity-domains-list',
			OpportunityQuery::DOMAIN_LINKS => 'admin/partials/opportunity-domain-links-list',
			OpportunityQuery::CONTENT      => 'admin/partials/opportunity-content-list',
			OpportunityQuery::POST_LINKS   => 'admin/partials/opportunity-post-links-list',
		);

		return new WP_REST_Response(
			array(
				'data' => array(
					'html'    => $this->template->fetch(
						$partials[ $kind ],
						array(
							'list'         => $page,
							'query'        => $query,
							'domain'       => $domain,
							'url'          => $on_product ? ProductScreen::opportunities_url( $query->product, $query, 1 ) : OpportunitiesScreen::url( $query, 1, $domain, $filter, (int) $request['post'] ),
							'product_page' => $on_product,
						)
					),
					'term'    => $query->search,
					'page'    => $page->current(),
					'orderby' => $query->orderby,
					'order'   => $query->order,
					'total'   => $page->total,
					'url'     => $on_product ? ProductScreen::opportunities_url( $query->product, $query, $page->current() ) : OpportunitiesScreen::url( $query, $page->current(), $domain, $filter, (int) $request['post'] ),
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
