<?php
/**
 * The Dashboard's export of the whole library (Task 38).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Application\Groups\GroupService;
use Affibloom\Application\Products\ProductService;
use Affibloom\Domain\Export\SpreadsheetCell;
use Affibloom\Domain\Product\LinkKind;
use Affibloom\Domain\Product\SearchTerm;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Module;
use Generator;
use WP_Error;
use WP_REST_Response;
use WP_REST_Server;

/**
 * GET /affibloom/v1/products/export (Task 38; F37 N1, "Export them first."): every published product with every field,
 * as `affibloom-products-<Y-m-d>.csv`, through the same writer as the alert tabs' export (CsvResponse, deviation 149).
 * The columns are ProductResource::full()'s keys, in its order, so a field added there is exported too; switches are
 * 1 or 0, lists (buttons, groups) JSON, every cell guarded against spreadsheet formulas. No credential or key is a
 * product field, so none can be a column. Products are read 100 at a time.
 */
final class ProductsExportController implements Module {

	/** Products read per page. */
	private const PAGE = 100;

	/**
	 * Builds the controller.
	 *
	 * @param ProductService  $products  The library.
	 * @param GroupService    $groups    Each product's groups, one query per page.
	 * @param ProductResource $fields    A product's fields, as the REST API gives them.
	 */
	public function __construct( private ProductService $products, private GroupService $groups, private ProductResource $fields ) {}

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
			'/products/export',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'export' ),
				'permission_callback' => static fn(): bool => current_user_can( Capabilities::PRODUCTS ),
			)
		);
	}

	/**
	 * The CSV.
	 */
	public function export(): WP_REST_Response|WP_Error {
		return CsvResponse::of( $this->rows(), 'affibloom-products-' . wp_date( 'Y-m-d' ) . '.csv' );
	}

	/**
	 * The header row, then one row per product.
	 *
	 * @return Generator<int, list<string>>
	 */
	private function rows(): Generator {
		$header = null;
		$page   = 1;
		do {
			$list   = $this->products->list( SearchTerm::from_input( '' ), LinkKind::All, $page, self::PAGE );
			$groups = $this->groups->for_products( array_map( static fn( $product ): int => (int) $product->id, $list->items ) );
			foreach ( $list->items as $product ) {
				$values = $this->fields->full( $product, $groups[ (int) $product->id ] ?? array() );
				if ( null === $header ) {
					$header = array_keys( $values );
					yield $header;
				}
				yield array_map( array( self::class, 'cell' ), array_values( $values ) );
			}
			++$page;
		} while ( $page <= $list->pages() );
	}

	/**
	 * One field as a CSV cell.
	 *
	 * @param mixed $value The field.
	 */
	private static function cell( mixed $value ): string {
		$text = match ( true ) {
			is_bool( $value )  => $value ? '1' : '0',
			is_array( $value ) => (string) wp_json_encode( $value ),
			null === $value    => '',
			default            => (string) $value,
		};

		return SpreadsheetCell::guard( $text );
	}
}
