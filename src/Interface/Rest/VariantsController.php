<?php
/**
 * REST route for a product's URL variants: affibloom/v1/products/{id}/variants (F27, Task V2).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Application\Products\ProductService;
use Affibloom\Application\Variants\VariantService;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Variant\VariantKind;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Interface\Admin\VariantRows;
use Affibloom\Interface\Template;
use Affibloom\Module;
use RuntimeException;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * PUT stores the product's whole list, as Lasso's lasso_save_url_variants does (R11, R14, R16), and answers with the
 * rows drawn again and the count; a refusal is 422 with the words under that row's key (`variants.<i>.key`, D2).
 * Lasso's `edit_post` and nonce checks are Capabilities::PRODUCTS and the REST nonce, as everywhere.
 */
final class VariantsController implements Module {

	/**
	 * Builds the controller.
	 *
	 * @param ProductService $products The products.
	 * @param VariantService $variants Their variants.
	 * @param Template       $template The rows' template.
	 */
	public function __construct( private ProductService $products, private VariantService $variants, private Template $template ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
	}

	/**
	 * Registers the route.
	 */
	public function routes(): void {
		register_rest_route(
			ProductsController::NAMESPACE,
			'/products/(?P<id>\d+)/variants',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'replace' ),
					'permission_callback' => static fn(): bool => current_user_can( Capabilities::PRODUCTS ),
					'args'                => array(
						'id'       => array(
							'type'    => 'integer',
							'minimum' => 1,
						),
						'variants' => array(
							'type'     => 'array',
							'required' => true,
							'items'    => array(
								'type'       => 'object',
								'properties' => array(
									'name' => array( 'type' => 'string' ),
									'key'  => array( 'type' => 'string' ),
								),
							),
						),
					),
				),
			)
		);
	}

	/**
	 * PUT /products/{id}/variants: the whole list. Names go through wp_kses_post and keys are normalised for the
	 * product's kind (R3: sanitize_title for a permalink, sanitize_text_field for a tracking ID), before any check.
	 *
	 * @param WP_REST_Request $request Request with "id" and "variants".
	 * @phpstan-param WP_REST_Request<array{id: int, variants: list<array{name?: string, key?: string}>}> $request
	 */
	public function replace( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$product = $this->products->get( (int) $request['id'] );
		if ( null === $product ) {
			return new WP_Error( 'affibloom_product_not_found', __( 'This product does not exist.', 'affibloom' ), array( 'status' => 404 ) );
		}
		$permalink = VariantKind::Permalink === VariantKind::of( $product );
		$rows      = array();
		foreach ( (array) $request['variants'] as $row ) {
			$key    = trim( (string) ( $row['key'] ?? '' ) );
			$rows[] = array(
				'name' => trim( wp_kses_post( (string) ( $row['name'] ?? '' ) ) ),
				'key'  => $permalink ? sanitize_title( $key ) : sanitize_text_field( $key ),
			);
		}

		try {
			$stored = $this->variants->replace( $product, $rows );
		} catch ( InvalidField $error ) {
			return FieldError::from( $error );
		} catch ( RuntimeException $error ) {
			return new WP_Error( 'affibloom_variants_not_saved', __( 'Could not save variants.', 'affibloom' ), array( 'status' => 500 ) );
		}
		$view = VariantRows::of( $product, $stored );

		return new WP_REST_Response(
			array(
				'data' => array(
					'variants' => $view,
					'count'    => count( $view ),
					'html'     => $this->template->fetch( 'admin/partials/variant-rows', array( 'rows' => $view ) ),
				),
			)
		);
	}
}
