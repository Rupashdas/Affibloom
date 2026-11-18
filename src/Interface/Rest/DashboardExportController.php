<?php
/**
 * The Dashboard's Export CSV of the broken and out-of-stock views.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Application\Groups\GroupService;
use Affibloom\Application\LinkHealth\IssueLinksExport;
use Affibloom\Application\LinkHealth\LinkIssueQuery;
use Affibloom\Domain\LinkHealth\AlertListing;
use Affibloom\Domain\Product\LinkKind;
use Affibloom\Domain\Product\SearchTerm;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Module;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * GET /affibloom/v1/dashboard/export (F11 R28, D18; Task 31D; Lasso's lasso_export_wp_all_import_csv): the list as
 * searched, filtered and sorted, on the broken-links or out-of-stock tab only (another tab: 400
 * affibloom_export_filter, Lasso's message), as `affibloom-<filter>-<Y-m-d>.csv`. The CSV is sent as it is, not as
 * JSON; `X-Affibloom-Exported` carries the number of rows for the "Exported N links." message.
 */
final class DashboardExportController implements Module {

	/** The header that carries the number of rows. */
	public const COUNT_HEADER = 'X-Affibloom-Exported';

	/**
	 * Builds the controller.
	 *
	 * @param IssueLinksExport $export The CSV's rows.
	 * @param LinkIssueQuery   $alerts The tab and the sort, with the grace days.
	 * @param GroupService     $groups The Group filter.
	 */
	public function __construct( private IssueLinksExport $export, private LinkIssueQuery $alerts, private GroupService $groups ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
		add_filter( 'rest_pre_serve_request', array( $this, 'serve' ), 10, 4 );
	}

	/**
	 * Registers the route.
	 */
	public function routes(): void {
		register_rest_route(
			ProductsController::NAMESPACE,
			'/dashboard/export',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'export' ),
					'permission_callback' => static fn(): bool => current_user_can( Capabilities::PRODUCTS ),
					'args'                => array(
						's'          => array(
							'type'    => 'string',
							'default' => '',
						),
						'link_type'  => array(
							'type'    => 'string',
							'default' => '',
						),
						'group'      => array(
							'type'    => 'integer',
							'default' => 0,
						),
						'filter'     => array(
							'type'    => 'string',
							'default' => '',
						),
						'order_by'   => array(
							'type'    => 'string',
							'default' => '',
						),
						'order_type' => array(
							'type'    => 'string',
							'default' => '',
						),
					),
				),
			)
		);
	}

	/**
	 * The CSV, or 400 on a tab that has none.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request The list's arguments.
	 * @return WP_REST_Response|WP_Error
	 */
	public function export( WP_REST_Request $request ) {
		$filter = (string) $request->get_param( 'filter' );
		if ( ! in_array( $filter, array( AlertListing::BROKEN, AlertListing::OUT_OF_STOCK ), true ) ) {
			return new WP_Error( 'affibloom_export_filter', __( 'Export is only available on broken-links and out-of-stock views.', 'affibloom' ), array( 'status' => 400 ) );
		}
		$order   = 'detection_date' === $request->get_param( 'order_by' ) ? (string) $request->get_param( 'order_type' ) : '';
		$listing = $this->alerts->listing( $order, $filter );
		$term    = SearchTerm::from_input( sanitize_text_field( (string) $request->get_param( 's' ) ) );
		$group   = $this->groups->choose_filter( (int) $request->get_param( 'group' ) );

		$stream = fopen( 'php://temp', 'w+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- an in-memory buffer, not a file.
		if ( false === $stream ) {
			return new WP_Error( 'affibloom_export_stream', __( 'Could not open export stream.', 'affibloom' ), array( 'status' => 500 ) );
		}
		$count = -1;
		foreach ( $this->export->rows( $term, LinkKind::from_input( (string) $request->get_param( 'link_type' ) ), $group, $listing ) as $row ) {
			fputcsv( $stream, $row, ',', '"', '' );
			++$count;
		}
		rewind( $stream );
		$csv = (string) stream_get_contents( $stream );
		fclose( $stream ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- the buffer above.

		$response = new WP_REST_Response( $csv );
		$response->header( 'Content-Type', 'text/csv; charset=utf-8' );
		$response->header( 'Content-Disposition', 'attachment; filename="affibloom-' . $filter . '-' . wp_date( 'Y-m-d' ) . '.csv"' );
		$response->header( self::COUNT_HEADER, (string) $count );
		$response->header( 'Access-Control-Expose-Headers', self::COUNT_HEADER );

		return $response;
	}

	/**
	 * Sends this route's CSV as it is instead of JSON.
	 *
	 * @param bool             $served  Whether the request was already served.
	 * @param WP_HTTP_Response $result  The response.
	 * @param WP_REST_Request  $request The request.
	 * @param WP_REST_Server   $server  The server.
	 * @return bool
	 */
	public function serve( $served, $result, $request, $server ): bool { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- WordPress passes four.
		unset( $server );
		if ( $served || '/' . ProductsController::NAMESPACE . '/dashboard/export' !== $request->get_route() || 200 !== $result->get_status() ) {
			return (bool) $served;
		}
		echo $result->get_data(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- a CSV file, its cells written by fputcsv() and guarded against formulas.

		return true;
	}
}
