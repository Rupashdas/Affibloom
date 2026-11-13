<?php
/**
 * The Dashboard without page reloads: the list (affibloom/v1/dashboard/products) and saved views
 * (affibloom/v1/dashboard/views).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Application\Dashboard\SavedViewService;
use Affibloom\Application\Products\ProductService;
use Affibloom\Domain\Dashboard\SavedView;
use Affibloom\Domain\Dashboard\SavedViews;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\LinkKind;
use Affibloom\Domain\Product\SearchTerm;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Interface\Admin\DashboardUrl;
use Affibloom\Interface\Admin\PerPage;
use Affibloom\Interface\Messages;
use Affibloom\Interface\Template;
use Affibloom\Module;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * The Dashboard without page reloads, as Lasso's list is an AJAX view (`lasso_report_urls`) and its saved views
 * are saved and deleted in place.
 *
 * Responses carry markup rendered by the same partials the page prints (Task 13D), so markup and escaping stay
 * in PHP and the script holds no second copy of a row or a pill. The list response also carries the screen's own
 * address for the list, which the script pushes into the browser's history.
 */
final class DashboardController implements Module {

	/** The link-type values a request may send: Lasso's. */
	private const LINK_TYPES = array( '', 'amazon', 'non_amazon' );

	/**
	 * Builds the controller.
	 *
	 * @param ProductService   $products The product library.
	 * @param PerPage          $per_page How many products this editor sees at a time.
	 * @param SavedViewService $views    This editor's saved views (Task 13E).
	 * @param Template         $template Renders the partials.
	 */
	public function __construct(
		private ProductService $products,
		private PerPage $per_page,
		private SavedViewService $views,
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
		$list = array(
			's'         => array(
				'type'    => 'string',
				'default' => '',
			),
			'link_type' => array(
				'type'    => 'string',
				'enum'    => self::LINK_TYPES,
				'default' => '',
			),
			// 0 asks for the size this editor chose last (PerPage).
			'per_page'  => array(
				'type'    => 'integer',
				'minimum' => 0,
				'default' => 0,
			),
		);

		register_rest_route(
			ProductsController::NAMESPACE,
			'/dashboard/products',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'products' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => $list + array(
						'paged' => array(
							'type'    => 'integer',
							'minimum' => 1,
							'default' => 1,
						),
					),
				),
			)
		);

		register_rest_route(
			ProductsController::NAMESPACE,
			'/dashboard/views',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_view' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => $list + array(
						'name' => array(
							'type'     => 'string',
							'required' => true,
						),
						// An existing view's ID replaces it; none adds a new view.
						'id'   => array(
							'type'    => 'string',
							'default' => '',
						),
					),
				),
			)
		);

		register_rest_route(
			ProductsController::NAMESPACE,
			'/dashboard/views/(?P<id>[A-Za-z0-9_-]{1,64})',
			array(
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_view' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
			)
		);
	}

	/**
	 * Only people who manage products see the list or keep views, as on the screen itself.
	 */
	public function can_manage(): bool {
		return current_user_can( Capabilities::PRODUCTS );
	}

	/**
	 * GET /dashboard/products: one page of the list, as the screen would print it.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request Request with s, link_type, paged and per_page.
	 */
	public function products( WP_REST_Request $request ): WP_REST_Response {
		$term      = SearchTerm::from_input( sanitize_text_field( (string) $request->get_param( 's' ) ) );
		$link_kind = LinkKind::from_input( (string) $request->get_param( 'link_type' ) );
		$per_page  = $this->per_page->choose( absint( $request->get_param( 'per_page' ) ) );
		$list      = $this->products->list( $term, $link_kind, max( 1, absint( $request->get_param( 'paged' ) ) ), $per_page );

		return new WP_REST_Response(
			array(
				'data' => array(
					'html'      => $this->template->fetch(
						'admin/partials/product-list',
						array(
							'products' => $list,
							'term'     => $term->text(),
							'url'      => DashboardUrl::to( $term->text(), $link_kind, 1, $per_page ),
						)
					),
					'term'      => $term->text(),
					'link_type' => $link_kind->value,
					'page'      => $list->current(),
					'per_page'  => $per_page,
					'total'     => $list->total,
					'url'       => DashboardUrl::to( $term->text(), $link_kind, $list->current(), $per_page ),
				),
			)
		);
	}

	/**
	 * POST /dashboard/views: Lasso's "Save as". A name Lasso would refuse, or a 31st view, is a 422 with the field's
	 * message (the API contract's `data.fields`).
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request Request with name, id, s, link_type and per_page.
	 * @return WP_REST_Response|WP_Error
	 */
	public function save_view( WP_REST_Request $request ) {
		$id   = sanitize_key( (string) $request->get_param( 'id' ) );
		$view = new SavedView(
			'' !== $id ? $id : wp_generate_uuid4(),
			sanitize_text_field( (string) $request->get_param( 'name' ) ),
			SearchTerm::from_input( sanitize_text_field( (string) $request->get_param( 's' ) ) )->text(),
			LinkKind::from_input( (string) $request->get_param( 'link_type' ) ),
			$this->per_page->choose( absint( $request->get_param( 'per_page' ) ) )
		);

		try {
			$views = $this->views->save( get_current_user_id(), $view );
		} catch ( InvalidField $error ) {
			return new WP_Error(
				'affibloom_invalid_field',
				Messages::for_reason( $error->reason ),
				array(
					'status' => 422,
					'fields' => array( $error->field => Messages::for_reason( $error->reason ) ),
				)
			);
		}

		return $this->views_response( $views, $view );
	}

	/**
	 * DELETE /dashboard/views/{id}: the pill's ×, after Lasso's "Delete saved view?". The editor's links are not
	 * touched.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request Request with the view's ID in the path.
	 */
	public function delete_view( WP_REST_Request $request ): WP_REST_Response {
		return $this->views_response( $this->views->delete( get_current_user_id(), (string) $request->get_param( 'id' ) ), null );
	}

	/**
	 * The saved-view bar as the page would print it, with the view just saved marked as the one in use.
	 *
	 * @param SavedViews     $views  The editor's views.
	 * @param SavedView|null $active The view just saved, or null.
	 */
	private function views_response( SavedViews $views, ?SavedView $active ): WP_REST_Response {
		return new WP_REST_Response(
			array(
				'data' => array(
					'html' => $this->template->fetch(
						'admin/partials/saved-views',
						array(
							'views'     => $views,
							'term'      => null === $active ? '' : $active->term,
							'link_kind' => null === $active ? LinkKind::All : $active->link_kind,
							'per_page'  => null === $active ? 0 : $active->per_page,
						)
					),
					'id'   => null === $active ? '' : $active->id,
				),
			)
		);
	}
}
