<?php
/**
 * The comparison tables' routes.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Application\Products\ProductInput;
use Affibloom\Application\Tables\TableInUse;
use Affibloom\Application\Tables\TableService;
use Affibloom\Domain\Fields\FieldType;
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
 * settings; `/tables/{id}/products` (T4, T5) adds a product or a group's, badges and removes them; `/groups`, their
 * `/fields` and `/values` (T6) shape the rows and columns and save the cells; `/order` and a field's `/move` (T9)
 * reorder them; `/locations` (T10) lists the posts a table is in;
 * `/clone` and DELETE (T11) copy and delete one;
 * `/preview` (T15) draws it as readers see it. `GET /tables` (T1; R8, R55, D3): one page of the list as the Tables screen draws it
 * (`html`, `url`) and as data (`items`: id, title) for the editor's "Choose a Table", ten a page or the picker's five;
 * anyone who may place displays may read it.
 */
final class TablesController implements Module {

	/**
	 * Builds the controller.
	 *
	 * @param TableService      $tables   The tables.
	 * @param Template          $template The list's template.
	 * @param TablePreview|null $preview  The reader's table for Preview Table (T15).
	 */
	public function __construct( private TableService $tables, private Template $template, private ?TablePreview $preview = null ) {}

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
			'/tables/(?P<id>\d+)/preview',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'preview' ),
				'permission_callback' => $can,
			)
		);
		register_rest_route(
			ProductsController::NAMESPACE,
			'/tables/(?P<id>\d+)/clone',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'clone' ),
				'permission_callback' => $can,
			)
		);
		register_rest_route(
			ProductsController::NAMESPACE,
			'/tables/(?P<id>\d+)/locations',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'places' ),
				'permission_callback' => $can,
				'args'                => array(
					'page' => array(
						'type'    => 'integer',
						'minimum' => 1,
						'default' => 1,
					),
				),
			)
		);
		register_rest_route(
			ProductsController::NAMESPACE,
			'/tables/(?P<id>\d+)/order',
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'order' ),
				'permission_callback' => $can,
				'args'                => array(
					'revision' => $revision,
					'products' => array(
						'type'  => 'array',
						'items' => array( 'type' => 'integer' ),
					),
					'groups'   => array(
						'type'  => 'array',
						'items' => array( 'type' => 'string' ),
					),
					'cell'     => array( 'type' => 'object' ),
				),
			)
		);
		register_rest_route(
			ProductsController::NAMESPACE,
			'/tables/(?P<id>\d+)/groups/(?P<group>[A-Za-z0-9_-]+)/fields/(?P<field>\d+)/move',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'move_field' ),
				'permission_callback' => $can,
				'args'                => array(
					'revision' => $revision,
					'to_group' => array(
						'type'     => 'string',
						'required' => true,
					),
					'position' => array(
						'type'    => 'integer',
						'minimum' => 0,
						'default' => 0,
					),
				),
			)
		);
		register_rest_route(
			ProductsController::NAMESPACE,
			'/tables/(?P<id>\d+)/groups',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'add_line' ),
				'permission_callback' => $can,
				'args'                => array( 'revision' => $revision ),
			)
		);
		register_rest_route(
			ProductsController::NAMESPACE,
			'/tables/(?P<id>\d+)/groups/(?P<group>[A-Za-z0-9_-]+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'remove_group' ),
				'permission_callback' => $can,
				'args'                => array( 'revision' => $revision ),
			)
		);
		register_rest_route(
			ProductsController::NAMESPACE,
			'/tables/(?P<id>\d+)/groups/(?P<group>[A-Za-z0-9_-]+)/fields',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'add_field' ),
				'permission_callback' => $can,
				'args'                => array(
					'revision' => $revision,
					'field'    => array(
						'type'     => 'integer',
						'minimum'  => 1,
						'required' => true,
					),
				),
			)
		);
		register_rest_route(
			ProductsController::NAMESPACE,
			'/tables/(?P<id>\d+)/groups/(?P<group>[A-Za-z0-9_-]+)/fields/(?P<field>\d+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'remove_field' ),
				'permission_callback' => $can,
				'args'                => array( 'revision' => $revision ),
			)
		);
		register_rest_route(
			ProductsController::NAMESPACE,
			'/tables/(?P<id>\d+)/values/(?P<product>\d+)/(?P<field>\d+)',
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'set_value' ),
				'permission_callback' => $can,
				'args'                => array(
					'revision' => $revision,
					'value'    => array(
						'type'     => 'string',
						'required' => true,
					),
				),
			)
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
				array(
					'methods'             => 'PATCH',
					'callback'            => array( $this, 'update' ),
					'permission_callback' => $can,
					'args'                => array(
						'revision'        => $revision,
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
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete' ),
					'permission_callback' => $can,
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
	 * POST /tables/{id}/groups (R25; Task T6): Add Row / Add Column, an empty one last. 409 `affibloom_table_no_products`,
	 * 409 `affibloom_table_empty_group`.
	 *
	 * @param WP_REST_Request $request The table and its revision.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 */
	public function add_line( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		return $this->write(
			fn(): ?Table => $this->tables->add_line( (int) $request['id'], (int) $request['revision'] ),
			self::redrawn( ... )
		);
	}

	/**
	 * DELETE /tables/{id}/groups/{group}?revision= (R33; Task T6): a whole row out.
	 *
	 * @param WP_REST_Request $request The table, its revision and the group.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 */
	public function remove_group( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		return $this->write(
			fn(): ?Table => $this->tables->remove_group( (int) $request['id'], (int) $request['revision'], (string) $request['group'] ),
			self::redrawn( ... )
		);
	}

	/**
	 * POST /tables/{id}/groups/{group}/fields (R28; Task T6): a field for every product of that row or column. 409
	 * `affibloom_table_field_exists`, 409 `affibloom_table_no_products`, 422 `affibloom_table_field_invalid`.
	 *
	 * @param WP_REST_Request $request The table, its revision, the group and the field.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 */
	public function add_field( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		return $this->write(
			fn(): ?Table => $this->tables->add_field( (int) $request['id'], (int) $request['revision'], (string) $request['group'], (int) $request['field'] ),
			self::redrawn( ... )
		);
	}

	/**
	 * DELETE /tables/{id}/groups/{group}/fields/{field}?revision= (R31, D7; Task T6): the field out, its values kept.
	 *
	 * @param WP_REST_Request $request The table, its revision, the group and the field.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 */
	public function remove_field( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		return $this->write(
			fn(): ?Table => $this->tables->remove_field( (int) $request['id'], (int) $request['revision'], (string) $request['group'], (int) $request['field'] ),
			self::redrawn( ... )
		);
	}

	/**
	 * PUT /tables/{id}/values/{product}/{field} (R30, D11; Tasks T6-T8): one cell, cleaned for its field's kind; the
	 * builder is not redrawn (the cell keeps its focus). 404 when the cell is not in the table.
	 *
	 * @param WP_REST_Request $request The table, its revision, the product, the field and the value.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 */
	public function set_value( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$field = $this->tables->field( (int) $request['field'] );
		if ( null === $field ) {
			return self::broken( 'cell_missing' );
		}
		// A button (R35; Task T8): `value` is Lasso's {"button_text", "url"}; the builder is drawn again.
		if ( FieldType::Button === $field->type ) {
			$button = json_decode( (string) $request['value'], true );
			$button = is_array( $button ) ? $button : array();
			try {
				return $this->write(
					fn(): ?Table => $this->tables->set_button(
						(int) $request['id'],
						(int) $request['revision'],
						(int) $request['product'],
						$field->id,
						wp_kses_post( is_string( $button['button_text'] ?? null ) ? $button['button_text'] : '' ),
						esc_url_raw( is_string( $button['url'] ?? null ) ? $button['url'] : '' )
					),
					self::redrawn( ... )
				);
			} catch ( InvalidField $error ) {
				return FieldError::from( $error );
			}
		}
		$value = TableCellValue::clean( $field, $request['value'] );

		return $this->write(
			fn(): ?Table => $this->tables->set_value( (int) $request['id'], (int) $request['revision'], (int) $request['product'], $field->id, $value ),
			static fn( Table $table ): array => array(
				'revision' => $table->revision,
				'value'    => $value,
			)
		);
	}

	/**
	 * PUT /tables/{id}/order (R36-R38; Task T9): `products`, `groups` or `cell` ({group, fields}) in a new order. 409
	 * `affibloom_table_invalid_order` (Lasso's "Invalid data").
	 *
	 * @param WP_REST_Request $request The table, its revision and the order.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 */
	public function order( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$order = array_intersect_key( $request->get_params(), array_flip( array( 'products', 'groups', 'cell' ) ) );

		return $this->write(
			fn(): ?Table => $this->tables->order( (int) $request['id'], (int) $request['revision'], $order ),
			self::redrawn( ... )
		);
	}

	/**
	 * POST /tables/{id}/groups/{group}/fields/{field}/move (R39; Task T9): the field to another row or column. 409
	 * `affibloom_table_field_exists` when it is moved where it is.
	 *
	 * @param WP_REST_Request $request The table, its revision, the group, the field, `to_group` and `position`.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 */
	public function move_field( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		return $this->write(
			fn(): ?Table => $this->tables->move_field( (int) $request['id'], (int) $request['revision'], (string) $request['group'], (int) $request['field'], (string) $request['to_group'], (int) $request['position'] ),
			self::redrawn( ... )
		);
	}

	/**
	 * GET /tables/{id}/locations?page= (R40, D13; Task T10): one page of the posts the table is in, six a page, each with
	 * its editor, its address and its view link.
	 *
	 * @param WP_REST_Request $request The table and the page.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 */
	public function places( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$found = $this->tables->places( (int) $request['id'], (int) $request['page'] );
		if ( null === $found ) {
			return new WP_Error( 'affibloom_table_not_found', __( 'That table does not exist.', 'affibloom' ), array( 'status' => 404 ) );
		}
		$found['items'] = array_map(
			static fn( array $post ): array => array(
				'title'    => '' !== $post['title'] ? wp_specialchars_decode( $post['title'], ENT_QUOTES ) : __( '(no title)', 'affibloom' ),
				'edit_url' => (string) get_edit_post_link( $post['id'], 'raw' ),
				'url'      => (string) get_permalink( $post['id'] ),
			),
			$found['items']
		);

		return new WP_REST_Response( array( 'data' => $found ) );
	}

	/**
	 * POST /tables/{id}/clone (R10, D8; Task T11): "<name> - Copy" with everything; answers its id and the list page it
	 * is on (the last). 404 for a table that is gone.
	 *
	 * @param WP_REST_Request $request The table.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 */
	public function clone( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$copy = $this->tables->clone( (int) $request['id'] );
		if ( null === $copy ) {
			return self::not_found();
		}

		return new WP_REST_Response(
			array(
				'data' => array(
					'id'   => (int) $copy['table']->id,
					'page' => $copy['page'],
				),
			),
			201
		);
	}

	/**
	 * DELETE /tables/{id} (R41, D12; Task T11): a table no content shows; answers the Tables list's address. 409
	 * `affibloom_table_in_use` (Lasso's "Hold Up" sentence), 404 "Table is not found".
	 *
	 * @param WP_REST_Request $request The table.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 */
	public function delete( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		try {
			$deleted = $this->tables->delete( (int) $request['id'] );
		} catch ( TableInUse ) {
			return new WP_Error( 'affibloom_table_in_use', __( "You can't delete a Table if it is in use on your site. Remove this Table from your content first.", 'affibloom' ), array( 'status' => 409 ) );
		}
		if ( ! $deleted ) {
			return self::not_found();
		}

		return new WP_REST_Response( array( 'data' => array( 'redirect_to' => TablesScreen::url() ) ) );
	}

	/**
	 * Lasso's answer for a table that is gone.
	 */
	private static function not_found(): WP_Error {
		return new WP_Error( 'affibloom_table_not_found', __( 'Table is not found', 'affibloom' ), array( 'status' => 404 ) );
	}

	/**
	 * GET /tables/{id}/preview (R54, D18; Task T15): the table as readers see it, with its stylesheets, the `:root`
	 * block and the phone script, for the builder's preview frame. 404 for a table that is gone.
	 *
	 * @param WP_REST_Request $request The table.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 */
	public function preview( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$answer = $this->preview?->of( (int) $request['id'] );
		if ( null === $answer ) {
			return self::not_found();
		}

		return new WP_REST_Response( array( 'data' => $answer ) );
	}

	/**
	 * A write's answer that redraws the builder.
	 *
	 * @param Table  $table The table as saved.
	 * @param string $html  The builder drawn from it.
	 * @return array{revision: int, html: string}
	 */
	private static function redrawn( Table $table, string $html ): array {
		return array(
			'revision' => $table->revision,
			'html'     => $html,
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
			'no_products'     => new WP_Error( 'affibloom_table_no_products', __( 'Please add a Product before adding a field', 'affibloom' ), array( 'status' => 409 ) ),
			'empty_group'     => new WP_Error( 'affibloom_table_empty_group', __( 'Please add a Field to the empty column before adding another', 'affibloom' ), array( 'status' => 409 ) ),
			'field_exists'    => new WP_Error( 'affibloom_table_field_exists', __( 'This field already exists, please select another field', 'affibloom' ), array( 'status' => 409 ) ),
			'field_invalid'   => new WP_Error( 'affibloom_table_field_invalid', __( 'That field does not exist.', 'affibloom' ), array( 'status' => 422 ) ),
			'group_missing', 'field_missing' => new WP_Error( 'affibloom_table_field_missing', __( 'That field is no longer in this table. Reload to see the latest version.', 'affibloom' ), array( 'status' => 404 ) ),
			'cell_missing'    => new WP_Error( 'affibloom_table_cell_missing', __( 'Unexpected error. Please contact your developer', 'affibloom' ), array( 'status' => 404 ) ),
			'invalid_order'   => new WP_Error( 'affibloom_table_invalid_order', __( 'Invalid data', 'affibloom' ), array( 'status' => 409 ) ),
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
