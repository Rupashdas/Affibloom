<?php
/**
 * The comparison tables' routes.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Application\Products\ProductInput;
use Affibloom\Application\Tables\TableService;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\SearchTerm;
use Affibloom\Domain\Table\Table;
use Affibloom\Domain\Table\TableChanged;
use Affibloom\Domain\Table\TablePage;
use Affibloom\Domain\Table\TableRuleBroken;
use Affibloom\Domain\Table\TableStyle;
use Affibloom\Domain\Table\TableTheme;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Interface\Admin\TablesScreen;
use Affibloom\Interface\Template;
use Affibloom\Module;
use Closure;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * F25's routes (Tasks T1-T17). `POST /tables` (T2, R12) creates a table; `PATCH /tables/{id}` (T3) saves the builder's
 * settings; `/tables/{id}/products` (T4, T5) adds a product or a group's, badges and removes them. `GET /tables` (T1; R8, R55, D3): one page of the list as the Tables screen draws it
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
		$can      = static fn(): bool => current_user_can( Capabilities::PRODUCTS );
		$revision = array(
			'type'     => 'integer',
			'minimum'  => 0,
			'required' => true,
		);
		register_rest_route(
			ProductsController::NAMESPACE,
			'/tables/(?P<id>\d+)/products',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'add_product' ),
				'permission_callback' => $can,
				'args'                => array(
					'revision' => $revision,
					'product'  => array(
						'type'    => 'integer',
						'minimum' => 0,
						'default' => 0,
					),
					'group'    => array(
						'type'    => 'integer',
						'minimum' => 0,
						'default' => 0,
					),
				),
			)
		);
		register_rest_route(
			ProductsController::NAMESPACE,
			'/tables/(?P<id>\d+)/products/(?P<product>\d+)',
			array(
				array(
					'methods'             => 'PATCH',
					'callback'            => array( $this, 'badge' ),
					'permission_callback' => $can,
					'args'                => array(
						'revision' => $revision,
						'badge'    => array(
							'type'     => 'string',
							'required' => true,
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'remove_product' ),
					'permission_callback' => $can,
					'args'                => array( 'revision' => $revision ),
				),
			)
		);
		register_rest_route(
			ProductsController::NAMESPACE,
			'/tables/(?P<id>\d+)',
			array(
				'methods'             => 'PATCH',
				'callback'            => array( $this, 'update' ),
				'permission_callback' => static fn(): bool => current_user_can( Capabilities::PRODUCTS ),
				'args'                => array(
					'revision'        => array(
						'type'     => 'integer',
						'minimum'  => 0,
						'required' => true,
					),
					'title'           => array( 'type' => 'string' ),
					'style'           => array(
						'type' => 'string',
						'enum' => array( TableStyle::Column->value, TableStyle::Row->value ),
					),
					'theme'           => array(
						'type' => 'string',
						'enum' => TableTheme::ALL,
					),
					'show_headers'    => array( 'type' => 'boolean' ),
					'show_field_name' => array( 'type' => 'boolean' ),
				),
			)
		);
		register_rest_route(
			ProductsController::NAMESPACE,
			'/tables',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create' ),
					'permission_callback' => static fn(): bool => current_user_can( Capabilities::PRODUCTS ),
					'args'                => array(
						'title' => array(
							'type'    => 'string',
							'default' => '',
						),
						'style' => array(
							'type'    => 'string',
							'enum'    => array( TableStyle::Column->value, TableStyle::Row->value ),
							'default' => TableStyle::Column->value,
						),
					),
				),
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
				),
			)
		);
	}

	/**
	 * PATCH /tables/{id} (R14, R15, D4, D5; Task T3): the builder's settings saved at once; answers the table's new
	 * revision and title and the builder drawn again. 404 for a table that is gone, 409 `affibloom_table_changed` for a
	 * stale revision, 422 for a blank name.
	 *
	 * @param WP_REST_Request $request The table, its revision and the settings changed.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 */
	public function update( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$changes = array_intersect_key( $request->get_params(), array_flip( array( 'title', 'style', 'theme', 'show_headers', 'show_field_name' ) ) );
		if ( isset( $changes['title'] ) ) {
			$changes['title'] = sanitize_text_field( (string) $changes['title'] );
		}
		try {
			$table = $this->tables->update( (int) $request['id'], (int) $request['revision'], $changes );
		} catch ( TableChanged ) {
			return new WP_Error( 'affibloom_table_changed', __( 'This table was changed in another window. Reload to see the latest version.', 'affibloom' ), array( 'status' => 409 ) );
		} catch ( InvalidField $error ) {
			return FieldError::from( $error );
		}
		if ( null === $table ) {
			return new WP_Error( 'affibloom_table_not_found', __( 'That table does not exist.', 'affibloom' ), array( 'status' => 404 ) );
		}

		return new WP_REST_Response(
			array(
				'data' => array(
					'id'       => (int) $table->id,
					'revision' => $table->revision,
					'title'    => $table->name,
					'style'    => $table->style->value,
					'html'     => $this->builder( $table ),
				),
			)
		);
	}

	/**
	 * POST /tables/{id}/products (R17-R22, R24; Tasks T4-T5): a product added last, or a group's products (`group`);
	 * answers the revision, the builder drawn again and how many products the table has. 404 for a table that is gone,
	 * 409 `affibloom_table_product_exists`, 422 `affibloom_table_product_invalid` / `affibloom_table_group_invalid`,
	 * 409 `affibloom_table_changed`.
	 *
	 * @param WP_REST_Request $request The table, its revision and the product or the group.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 */
	public function add_product( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		return $this->write(
			fn(): ?Table => (int) $request['group'] > 0
				? $this->tables->add_group( (int) $request['id'], (int) $request['revision'], (int) $request['group'] )
				: $this->tables->add_product( (int) $request['id'], (int) $request['revision'], (int) $request['product'] ),
			static fn( Table $table, string $html ): array => array(
				'revision'      => $table->revision,
				'html'          => $html,
				'product_count' => count( $table->products ),
			)
		);
	}

	/**
	 * PATCH /tables/{id}/products/{product} (R21; Task T4): the product's badge, saved on leaving it.
	 *
	 * @param WP_REST_Request $request The table, its revision, the product and the badge.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 */
	public function badge( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$badge = ProductInput::icon_markup( $request['badge'] );

		return $this->write(
			fn(): ?Table => $this->tables->set_badge( (int) $request['id'], (int) $request['revision'], (int) $request['product'], $badge ),
			static fn( Table $table ): array => array( 'revision' => $table->revision )
		);
	}

	/**
	 * DELETE /tables/{id}/products/{product}?revision= (R23; Task T4): the product and its cells leave the table.
	 *
	 * @param WP_REST_Request $request The table, its revision and the product.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 */
	public function remove_product( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		return $this->write(
			fn(): ?Table => $this->tables->remove_product( (int) $request['id'], (int) $request['revision'], (int) $request['product'] ),
			static fn( Table $table, string $html ): array => array(
				'revision'      => $table->revision,
				'html'          => $html,
				'product_count' => count( $table->products ),
			)
		);
	}

	/**
	 * One builder write's answer (D4, D5): the change made, or the refusal in Lasso's words.
	 *
	 * @param Closure $write  The write: fn(): ?Table (null: the table is gone).
	 * @param Closure $answer The answer's data: fn( Table, string $html ): array.
	 */
	private function write( Closure $write, Closure $answer ): WP_REST_Response|WP_Error {
		try {
			$table = $write();
		} catch ( TableChanged ) {
			return new WP_Error( 'affibloom_table_changed', __( 'This table was changed in another window. Reload to see the latest version.', 'affibloom' ), array( 'status' => 409 ) );
		} catch ( TableRuleBroken $broken ) {
			return self::broken( $broken->rule );
		}
		if ( null === $table ) {
			return new WP_Error( 'affibloom_table_not_found', __( 'That table does not exist.', 'affibloom' ), array( 'status' => 404 ) );
		}

		return new WP_REST_Response( array( 'data' => $answer( $table, $this->builder( $table ) ) ) );
	}

	/**
	 * A table rule's refusal (R18 and the rest), in Lasso's words where Lasso has them.
	 *
	 * @param string $rule The rule broken.
	 */
	private static function broken( string $rule ): WP_Error {
		return match ( $rule ) {
			'product_exists'  => new WP_Error( 'affibloom_table_product_exists', __( 'This product already exists, please select another product', 'affibloom' ), array( 'status' => 409 ) ),
			'product_invalid' => new WP_Error( 'affibloom_table_product_invalid', __( 'That product does not exist.', 'affibloom' ), array( 'status' => 422 ) ),
			'group_invalid'   => new WP_Error( 'affibloom_table_group_invalid', __( 'That group does not exist.', 'affibloom' ), array( 'status' => 422 ) ),
			default           => new WP_Error( 'affibloom_table_product_missing', __( 'That product is no longer in this table. Reload to see the latest version.', 'affibloom' ), array( 'status' => 404 ) ),
		};
	}

	/**
	 * The builder drawn from the saved table (D5).
	 *
	 * @param Table $table The table.
	 */
	private function builder( Table $table ): string {
		return $this->template->fetch( 'admin/partials/table-builder', $this->tables->builder( $table ) );
	}

	/**
	 * POST /tables (R12; Task T2): Create Table. 201 with the table's ID and where to go; 422 for a blank name.
	 *
	 * @param WP_REST_Request $request The name and the style.
	 * @phpstan-param WP_REST_Request<array{title: string, style: string}> $request
	 */
	public function create( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		try {
			$table = $this->tables->create( sanitize_text_field( (string) $request['title'] ), TableStyle::from( (string) $request['style'] ) );
		} catch ( InvalidField $error ) {
			return FieldError::from( $error );
		}

		return new WP_REST_Response(
			array(
				'data' => array(
					'id'          => (int) $table->id,
					// The new table's builder (Task T3).
					'redirect_to' => TablesScreen::table_url( (int) $table->id ),
				),
			),
			201
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
