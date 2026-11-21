<?php
/**
 * Content Opportunities' Export CSV.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Application\Opportunities\ContentExport;
use Affibloom\Domain\Opportunity\OpportunityQuery;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Interface\Admin\OpportunitiesScreen;
use Affibloom\Module;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * GET /affibloom/v1/opportunities/content/export (F22 R57-R62, D19, D20; Task O9; Lasso's
 * lasso_export_content_opportunities_csv): Content's list as searched, filtered and sorted, one line per unmonetized
 * anchor, as `affibloom-content-opportunities-<Y-m-d>.csv`; the arguments are the Content list's own.
 */
final class ContentExportController implements Module {

	/**
	 * Builds the controller.
	 *
	 * @param ContentExport $export The file's lines.
	 */
	public function __construct( private ContentExport $export ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
		add_filter( 'rest_pre_serve_request', array( CsvResponse::class, 'serve' ), 10, 4 );
	}

	/**
	 * Registers the route.
	 */
	public function routes(): void {
		register_rest_route(
			ProductsController::NAMESPACE,
			'/opportunities/content/export',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'export' ),
				'permission_callback' => static fn(): bool => current_user_can( Capabilities::PRODUCTS ),
				'args'                => OpportunitiesController::list_args( OpportunityQuery::CONTENT ),
			)
		);
	}

	/**
	 * The CSV.
	 *
	 * @param WP_REST_Request $request The list's arguments.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 */
	public function export( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$query  = OpportunitiesScreen::query( OpportunityQuery::CONTENT, sanitize_text_field( (string) $request['search'] ), (string) $request['orderby'], (string) $request['order'], 1, 0 );
		$filter = OpportunitiesScreen::filter( (string) $request['show'], (string) $request['content_type'], (int) $request['category'], (int) $request['min_clicks'] );

		return CsvResponse::of( $this->export->rows( $query, $filter, time() ), 'affibloom-content-opportunities-' . wp_date( 'Y-m-d' ) . '.csv' );
	}
}
