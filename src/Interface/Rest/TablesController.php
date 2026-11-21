<?php
/**
 * The comparison tables' routes.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Application\Tables\TableService;
use Affibloom\Domain\Product\SearchTerm;
use Affibloom\Domain\Table\Table;
use Affibloom\Domain\Table\TablePage;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Interface\Admin\TablesScreen;
use Affibloom\Interface\Template;
use Affibloom\Module;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * F25's routes (Tasks T1-T17). `GET /tables` (T1; R8, R55, D3): one page of the list as the Tables screen draws it
 * (`html`, `url`) and as data (`items`: id, title) for the editor's "Choose a Table", ten a page or the picker's five;
 * anyone who may place displays may read it.
 */
final class TablesController implements Module {

	/**
	 * Builds the controller.
	 *
	 * @param TableService $tables   The tables.
	 * @param Template     $template The list's template.
	 */
	public function __construct( private TableService $tables, private Template $template ) {}

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
			'/tables',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'list' ),
				'permission_callback' => static fn(): bool => current_user_can( Capabilities::PRODUCTS ) || current_user_can( Capabilities::INSERT_BOXES ),
				'args'                => array(
					'search'   => array(
						'type'    => 'string',
						'default' => '',
					),
					'page'     => array(
						'type'    => 'integer',
						'minimum' => 1,
						'default' => 1,
					),
					'per_page' => array(
						'type'    => 'integer',
						'enum'    => array( 5, TablePage::PER_PAGE ),
						'default' => TablePage::PER_PAGE,
					),
				),
			)
		);
	}

	/**
	 * GET /tables.
	 *
	 * @param WP_REST_Request $request The search, the page and its size.
	 * @phpstan-param WP_REST_Request<array{search: string, page: int, per_page: int}> $request
	 */
	public function list( WP_REST_Request $request ): WP_REST_Response {
		$term   = SearchTerm::from_input( sanitize_text_field( (string) $request['search'] ) );
		$tables = $this->tables->list( $term, (int) $request['page'], (int) $request['per_page'] );

		return new WP_REST_Response(
			array(
				'data' => array(
					'html'  => $this->template->fetch(
						'admin/partials/tables-list',
						array(
							'tables'    => $tables,
							'locations' => $this->tables->locations( $tables ),
							'term'      => $term->text(),
							'url'       => TablesScreen::url( $term->text() ),
						)
					),
					'items' => array_map(
						static fn( Table $table ): array => array(
							'id'    => (int) $table->id,
							'title' => wp_specialchars_decode( $table->name, ENT_QUOTES ),
						),
						$tables->items
					),
					'term'  => $term->text(),
					'page'  => $tables->current(),
					'pages' => $tables->pages(),
					'total' => $tables->total,
					'url'   => TablesScreen::url( $term->text(), $tables->current() ),
				),
			)
		);
	}
}
