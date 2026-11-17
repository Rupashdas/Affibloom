<?php
/**
 * REST routes for products: affibloom/v1/products.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Application\Amazon\AmazonSearchService;
use Affibloom\Application\Amazon\SearchRefused;
use Affibloom\Application\Groups\GroupService;
use Affibloom\Application\Products\DuplicateLink;
use Affibloom\Application\Products\ProductNotFound;
use Affibloom\Application\Products\ProductService;
use Affibloom\Application\Products\RepointRefused;
use Affibloom\Application\Products\ValidationFailed;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\LinkKind;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Domain\Amazon\SearchPage;
use Affibloom\Domain\Product\SearchTerm;
use Affibloom\Interface\Admin\AdminMenu;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Interface\Admin\ProductScreen;
use Affibloom\Interface\Messages;
use Affibloom\Module;
use RuntimeException;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST routes for products: affibloom/v1/products.
 */
final class ProductsController implements Module {

	public const NAMESPACE = 'affibloom/v1';

	/**
	 * Builds the controller.
	 *
	 * @param ProductService      $products         Product use cases.
	 * @param ProductResource     $product_resource Products as arrays.
	 * @param GroupService        $groups           The groups a product is in (F23).
	 * @param AmazonSearchService $search       The Amazon tab's selections (C1M).
	 */
	public function __construct( private ProductService $products, private ProductResource $product_resource, private GroupService $groups, private AmazonSearchService $search ) {}

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
			self::NAMESPACE,
			'/products',
			array(
				// The product search of "Add to Group" (F23 D10) and of the editor's "Choose a Link" (F4 R13, Task 23B, five a
				// page): newest changed first, with a flag for the products already in the group asked about. Anyone who
				// may place displays may search (F4 D8).
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search' ),
					'permission_callback' => array( $this, 'can_insert' ),
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
						'group'    => array(
							'type'    => 'integer',
							'minimum' => 0,
							'default' => 0,
						),
						'per_page' => array(
							'type'    => 'integer',
							'minimum' => 1,
							'maximum' => 50,
							'default' => 10,
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => array(
						// Kept exactly as pasted: no sanitising and no length cap (Lasso has none); the domain validates it.
						'url'        => array(
							'type'    => 'string',
							'default' => '',
						),
						// The Amazon tab's selected search results, by ASIN (C1M): one, or up to ten in bulk mode.
						'selections' => array(
							'type'     => 'array',
							'items'    => array( 'type' => 'string' ),
							'maxItems' => SearchPage::SIZE,
							'default'  => array(),
						),
					),
				),
				// The Dashboard's selection bar → Delete (Task 17B): the ticked rows to the trash, as Lasso's
				// lasso_bulk_remove_links. At most a page of them: 50 is the largest page size.
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_many' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => array(
						'ids' => array(
							'type'     => 'array',
							'items'    => array(
								'type'    => 'integer',
								'minimum' => 1,
							),
							'minItems' => 1,
							'maxItems' => 50,
							'required' => true,
						),
					),
				),
			)
		);

		// Save Changes on the product page (Task 15A). Only the fields sent change.
		// Delete This Link on the product page (Task 17A): for good, as Lasso's lasso_delete_post.
		register_rest_route(
			self::NAMESPACE,
			'/products/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => self::field_args(),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
			)
		);

		// "This Product Already Exists" → Confirm (Task 15C).
		register_rest_route(
			self::NAMESPACE,
			'/products/(?P<id>\d+)/repoint',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'repoint' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => array(
						'to'  => array(
							'type'     => 'integer',
							'required' => true,
						),
						'url' => array(
							'type'     => 'string',
							'required' => true,
						),
					),
				),
			)
		);
	}

	/**
	 * The product page's fields and their types; WordPress refuses a wrong type (400), ProductInput and the service
	 * apply Lasso's rules to the rest.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function field_args(): array {
		$args = array();
		foreach ( array( 'name', 'url', 'button_text', 'theme', 'image_url', 'badge', 'price', 'description', 'disclosure' ) as $field ) {
			$args[ $field ] = array( 'type' => 'string' );
		}
		foreach ( array( 'new_tab', 'nofollow', 'sponsored', 'show_disclosure', 'prime', 'show_price' ) as $field ) {
			$args[ $field ] = array( 'type' => 'boolean' );
		}
		$args['image_id']         = array(
			'type'    => 'integer',
			'minimum' => 0,
		);
		$args['primary_position'] = array(
			'type'    => 'integer',
			'minimum' => 0,
		);
		// The groups the product is in: IDs of groups, or names that become groups (F23 R14), stored by the groups service.
		$args['groups'] = array(
			'type'  => 'array',
			'items' => array(
				'type' => array( 'integer', 'string' ),
			),
		);
		// The Features list (C1Q4): the bullets' texts in order.
		$args['features'] = array(
			'type'  => 'array',
			'items' => array( 'type' => 'string' ),
		);
		$args['buttons']  = array(
			'type'  => 'array',
			'items' => array(
				'type'       => 'object',
				'properties' => array(
					'url'      => array( 'type' => 'string' ),
					'text'     => array( 'type' => 'string' ),
					'new_tab'  => array( 'type' => 'boolean' ),
					'nofollow' => array( 'type' => 'boolean' ),
				),
			),
		);

		return $args;
	}

	/**
	 * PATCH /products/{id}: stores the fields sent and answers with the product as stored (S15).
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response|WP_Error 200 with the product; 404 for no such product; 422 with a message per field.
	 */
	public function update( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$input  = array_intersect_key( $request->get_params(), self::field_args() );
		$groups = array_key_exists( 'groups', $input ) ? (array) $input['groups'] : null;
		unset( $input['groups'] );

		try {
			$product = $this->products->update( (int) $request['id'], $input );
		} catch ( ProductNotFound $missing ) {
			return new WP_Error( 'affibloom_not_found', __( 'This product does not exist.', 'affibloom' ), array( 'status' => 404 ) );
		} catch ( DuplicateLink $duplicate ) {
			// Lasso's repoint_offer: nothing saved; both products for the dialog (url-save.php's second panel).
			$current = $this->products->get( (int) $request['id'] );
			return new WP_Error(
				'affibloom_duplicate',
				__( 'This Product Already Exists', 'affibloom' ),
				array(
					'status' => 409,
					'from'   => null === $current ? null : self::offer( $current ),
					'to'     => self::offer( $duplicate->other ),
				)
			);
		} catch ( ValidationFailed $refused ) {
			$fields = array_map( array( Messages::class, 'for_reason' ), $refused->errors );

			return new WP_Error(
				'affibloom_invalid',
				(string) reset( $fields ),
				array(
					'status' => 422,
					'fields' => $fields,
				)
			);
		}

		// After the product is stored: a refused save (422, 409) stores no groups either.
		if ( null !== $groups ) {
			$values = array();
			foreach ( $groups as $value ) {
				if ( is_int( $value ) || is_string( $value ) ) {
					$values[] = is_string( $value ) ? sanitize_text_field( $value ) : $value;
				}
			}
			$this->groups->set_for_product( (int) $product->id, $values );
		}
		$of = $this->groups->for_products( array( (int) $product->id ) );

		return new WP_REST_Response( array( 'data' => $this->product_resource->full( $product, $of[ (int) $product->id ] ?? array() ) ) );
	}

	/**
	 * GET /products?search=&page=&group=&per_page=: products by the Dashboard's search rule (spaces as wildcards), newest
	 * changed first, ten a page unless asked (the editor's "Choose a Link" asks five, F4 R13), each with whether it is in
	 * the group asked about (the "Add to Group" dialog, F23 R20).
	 *
	 * @param WP_REST_Request $request Request with search, page, group and per_page.
	 * @phpstan-param WP_REST_Request<array{search: string, page: int, group: int, per_page: int}> $request
	 */
	public function search( WP_REST_Request $request ): WP_REST_Response {
		$term  = SearchTerm::from_input( sanitize_text_field( (string) $request['search'] ) );
		$group = (int) $request['group'];
		$page  = $this->products->list( $term, LinkKind::All, max( 1, (int) $request['page'] ), max( 1, (int) $request['per_page'] ) );
		$items = array();
		foreach ( $page->items as $product ) {
			$items[] = array(
				'id'        => (int) $product->id,
				// WordPress stores a title's "&" as "&amp;"; the scripts print the name as text.
				'name'      => wp_specialchars_decode( $product->name, ENT_QUOTES ),
				'url'       => $product->url->value(),
				'image_url' => $product->image_url,
				'in_group'  => $group > 0 && $this->groups->has_member( $group, (int) $product->id ),
			);
		}

		return new WP_REST_Response(
			array(
				'data' => array(
					'items' => $items,
					'total' => $page->total,
					'page'  => $page->current(),
					'pages' => $page->pages(),
				),
			)
		);
	}

	/**
	 * DELETE /products/{id}: deletes the product for good (F1 requirements 11, X3, D33, D39). Anything that is not a
	 * published product is not found (Lasso answers "Invalid post." with 400; 404 is this API's word for it).
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response|WP_Error 200 { id, deleted: true }; 404 for no such product; 500 when WordPress refuses.
	 */
	public function delete( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$id = (int) $request['id'];
		try {
			$this->products->delete( $id );
		} catch ( ProductNotFound $missing ) {
			return new WP_Error( 'affibloom_not_found', __( 'This product does not exist.', 'affibloom' ), array( 'status' => 404 ) );
		} catch ( RuntimeException $refused ) {
			return new WP_Error( 'affibloom_not_deleted', __( 'Could not delete. Please try again.', 'affibloom' ), array( 'status' => 500 ) );
		}

		return new WP_REST_Response(
			array(
				'data' => array(
					'id'      => $id,
					'deleted' => true,
				),
			)
		);
	}

	/**
	 * DELETE /products?ids[]=…: moves the ticked products to the trash (F1 requirements 11, X7, D33, D39, D40). IDs that
	 * are not published products are skipped, as Lasso skips records it cannot edit; the answer lists those trashed.
	 * WordPress has already refused anything but 1-50 positive integers (400 rest_invalid_param).
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response 200 { deleted: [ids] }.
	 */
	public function delete_many( WP_REST_Request $request ): WP_REST_Response {
		$ids = array_map( 'intval', (array) $request['ids'] );

		return new WP_REST_Response( array( 'data' => array( 'deleted' => $this->products->trash( $ids ) ) ) );
	}

	/**
	 * POST /products/{id}/repoint: retires this product in favour of the one that has the link, and says where to go.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response|WP_Error 200 with redirect_to; 400 with Lasso's message when the repoint no longer fits.
	 */
	public function repoint( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		try {
			$kept = $this->products->repoint( (int) $request['id'], (int) $request['to'], (string) $request['url'] );
		} catch ( RepointRefused $refused ) {
			return new WP_Error( 'affibloom_repoint_refused', Messages::for_reason( $refused->reason ), array( 'status' => 400 ) );
		}

		return new WP_REST_Response(
			array( 'data' => array( 'redirect_to' => add_query_arg( 'affibloom_notice', 'repointed', ProductScreen::url( (int) $kept->id ) ) ) )
		);
	}

	/**
	 * One side of "This Product Already Exists": its name, its link and its page (Lasso's repoint_from_* / repoint_to_*).
	 *
	 * @param Product $product A stored product.
	 * @return array{id: int, name: string, url: string, page: string}
	 */
	private static function offer( Product $product ): array {
		return array(
			'id'   => (int) $product->id,
			'name' => $product->name,
			'url'  => $product->url->value(),
			'page' => ProductScreen::url( (int) $product->id ),
		);
	}

	/**
	 * Only people who manage products may add or change them (decision 1; Lasso's endpoint allowed edit_posts).
	 */
	public function can_manage(): bool {
		return current_user_can( Capabilities::PRODUCTS );
	}

	/**
	 * Anyone who may place displays in posts may search the products (F4 D8; Lasso's editor actions allow edit_posts).
	 */
	public function can_insert(): bool {
		return current_user_can( Capabilities::INSERT_BOXES );
	}

	/**
	 * POST /products: "Add A New Link" with one link. A link the site already has makes no product: 200 with
	 * "duplicate", as Lasso sends the editor to the existing one. With "selections" it adds the Amazon tab's selected
	 * results instead.
	 *
	 * @param WP_REST_Request $request Request with "url", or "selections".
	 * @phpstan-param WP_REST_Request<array{url: string, selections: list<string>}> $request
	 */
	public function create( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		// The route's schema makes it a list of strings, at most ten (empty when not sent).
		$selections = $request['selections'];
		if ( array() !== $selections ) {
			return $this->create_selected( $selections );
		}

		try {
			$added = $this->products->create_from_url( (string) $request['url'] );
		} catch ( InvalidField $error ) {
			return FieldError::from( $error );
		}

		$data = $this->product_resource->summary( $added->product );
		if ( $added->already_existed ) {
			$data['duplicate'] = true;
		}
		// Where Lasso goes after an add (url-add.php 1621-1639), with no message of its own: the product's page, Amazon
		// links too, with Lasso's warning when it already existed (?is_duplicate=true, url-details.php 2597-2600). Its
		// "is_amazon" branch never runs: check_error_and_response_ajax() sends no top-level is_amazon (deviation 133).
		$page                = ProductScreen::url( (int) $added->product->id );
		$data['redirect_to'] = $added->already_existed ? add_query_arg( 'is_duplicate', '1', $page ) : $page;

		return new WP_REST_Response( array( 'data' => $data ), $added->already_existed ? 200 : 201 );
	}

	/**
	 * The Amazon tab's selections (C1M; Lasso's marketplace_selection(s), R71): each result's data is stored first, then
	 * its product link is added as a pasted one would be, so it takes Amazon's name and image without a second request.
	 * Up to ten are added at once rather than in Lasso's background run: none of them asks Amazon or fetches a page.
	 * 201 with how many were added (found ones count) and the Dashboard, where Lasso's run of selections ends (url-add.php 1600); 400 when a selection
	 * is not one of the person's search results.
	 *
	 * @param list<string> $asins The selected ASINs.
	 */
	private function create_selected( array $asins ): WP_REST_Response|WP_Error {
		try {
			$urls = $this->search->selected( get_current_user_id(), $asins );
		} catch ( SearchRefused $refused ) {
			return new WP_Error( 'affibloom_invalid_selection', __( 'Invalid selection. Search again and select the product.', 'affibloom' ), array( 'status' => 400 ) );
		}

		$ids = array();
		try {
			foreach ( $urls as $url ) {
				$ids[] = (int) $this->products->create_from_url( $url )->product->id;
			}
		} catch ( InvalidField $error ) {
			return FieldError::from( $error );
		}

		return new WP_REST_Response(
			array(
				'data' => array(
					'ids'         => $ids,
					'redirect_to' => admin_url( 'admin.php?page=' . AdminMenu::SLUG ),
				),
			),
			201
		);
	}
}
