<?php
/**
 * The block editor's preview of a display.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Application\Fields\FieldsService;
use Affibloom\Domain\Amazon\AmazonProductRepository;
use Affibloom\Domain\Amazon\ShownPrice;
use Affibloom\Domain\Fields\ProductFields;
use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Domain\Shortcode\DisplayRequest;
use DateTimeImmutable;
use DateTimeZone;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Interface\Block\ProductBoxBlock;
use Affibloom\Module;
use WP_Block_Type_Registry;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * POST /editor/preview (F4 D2, R32, Task 23C): one display drawn from a Product box block's attributes, by the block's
 * own render callback, so the editor shows the HTML the published page will. Only Affibloom's displays, from attributes
 * that name one (DisplayRequest::names_display); never an arbitrary shortcode (unlike Lasso's R32).
 *
 * The Schema & Structured Data panel (Task 24B; R43-R47): GET /editor/schema reads what a Single display's schema would
 * say (the review's author, the price and its currency, the rating, Pros and Cons); POST /editor/schema/rating gives a
 * product without a Primary Rating Lasso's 3.5 (R46), a write to the product, never to the post.
 */
final class EditorController implements Module {

	/**
	 * Builds the controller.
	 *
	 * @param ProductRepository       $products The products.
	 * @param AmazonProductRepository $amazon   The stored Amazon data, for the price and currency.
	 * @param FieldsService           $fields   The products' fields, for the rating.
	 */
	public function __construct(
		private ProductRepository $products,
		private AmazonProductRepository $amazon,
		private FieldsService $fields,
	) {}

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
			'/editor/preview',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'preview' ),
				'permission_callback' => static fn(): bool => current_user_can( Capabilities::INSERT_BOXES ),
				'args'                => array(
					'attributes' => array(
						'type'     => 'object',
						'required' => true,
					),
				),
			)
		);

		$product = array(
			'product' => array(
				'type'     => 'integer',
				'minimum'  => 1,
				'required' => true,
			),
		);
		register_rest_route(
			ProductsController::NAMESPACE,
			'/editor/schema',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'schema' ),
				// The review's author is the post's: its editors only (R47).
				'permission_callback' => static fn( WP_REST_Request $request ): bool => current_user_can( Capabilities::INSERT_BOXES )
					&& ( 0 === (int) $request['post'] || current_user_can( 'edit_post', (int) $request['post'] ) ),
				'args'                => $product + array(
					'post' => array(
						'type'    => 'integer',
						'minimum' => 0,
						'default' => 0,
					),
				),
			)
		);
		register_rest_route(
			ProductsController::NAMESPACE,
			'/editor/schema/rating',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'rating' ),
				'permission_callback' => static fn(): bool => current_user_can( Capabilities::PRODUCTS ),
				'args'                => $product,
			)
		);
	}

	/**
	 * GET /editor/schema?product=&post=: { author, price, currency, rating, pros, cons } (R43). The price has two
	 * decimals ('' when the product shows none); the currency is Amazon's for an Amazon product, else USD, as the page's
	 * JSON-LD reads them (SchemaMarkup).
	 *
	 * @param WP_REST_Request $request Request with "product" and "post".
	 * @phpstan-param WP_REST_Request<array{product: int, post: int}> $request
	 * @return WP_REST_Response|WP_Error 200, or 404 for a product that is not published.
	 */
	public function schema( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$product = $this->products->get( (int) $request['product'] );
		if ( null === $product ) {
			return new WP_Error( 'affibloom_not_found', __( 'This product does not exist.', 'affibloom' ), array( 'status' => 404 ) );
		}
		$fields = $this->products->fields( (int) $product->id ) ?? ProductFields::from_array( array() );
		$stored = ProductType::Amazon === $product->type && '' !== $product->asin ? $this->amazon->find( $product->asin, $product->amazon_country ) : null;
		$price  = null !== $stored ? ShownPrice::of( $stored, new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) ) )->price : wp_strip_all_tags( $product->price );
		$number = preg_match( '/\d[\d,]*(?:\.\d+)?/', $price, $found ) ? (float) str_replace( ',', '', $found[0] ) : null;
		$post   = get_post( (int) $request['post'] );
		$lines  = static fn( int $id ): array => array_values( array_filter( array_map( 'trim', $fields->lines( $id ) ), static fn( string $line ): bool => '' !== $line ) );

		return new WP_REST_Response(
			array(
				'data' => array(
					'author'   => null === $post ? '' : (string) get_the_author_meta( 'display_name', (int) $post->post_author ),
					'price'    => null === $number ? '' : number_format( $number, 2, '.', '' ),
					'currency' => null !== $stored && '' !== $stored->currency ? $stored->currency : 'USD',
					'rating'   => $fields->shown_rating(),
					'pros'     => $lines( ProductFields::PROS_ID ),
					'cons'     => $lines( ProductFields::CONS_ID ),
				),
			)
		);
	}

	/**
	 * POST /editor/schema/rating { product }: Lasso's 3.5 for a product without a Primary Rating (R46); one that has it
	 * keeps it. Answers the rating the schema now reads.
	 *
	 * @param WP_REST_Request $request Request with "product".
	 * @phpstan-param WP_REST_Request<array{product: int}> $request
	 * @return WP_REST_Response|WP_Error 200 { rating }, or 404 for a product that is not published.
	 */
	public function rating( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$fields = $this->fields->attach( (int) $request['product'], ProductFields::RATING_ID );
		if ( null === $fields ) {
			return new WP_Error( 'affibloom_not_found', __( 'This product does not exist.', 'affibloom' ), array( 'status' => 404 ) );
		}

		return new WP_REST_Response( array( 'data' => array( 'rating' => $fields->shown_rating() ) ) );
	}

	/**
	 * The display's HTML: { html }. '' when the product is gone (the renderer's own rule, F4 edge case 1).
	 *
	 * @param WP_REST_Request $request Request with the block's attributes.
	 * @phpstan-param WP_REST_Request<array{attributes: array<string, mixed>}> $request
	 * @return WP_REST_Response|WP_Error 200 { data: { html } }; 400 affibloom_invalid_display for attributes that name no display.
	 */
	public function preview( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$attributes = (array) $request['attributes'];
		$block      = WP_Block_Type_Registry::get_instance()->get_registered( ProductBoxBlock::NAME );
		if ( null === $block || ! DisplayRequest::names_display( $attributes ) ) {
			return new WP_Error( 'affibloom_invalid_display', __( 'This display cannot be drawn.', 'affibloom' ), array( 'status' => 400 ) );
		}

		return new WP_REST_Response( array( 'data' => array( 'html' => $block->render( $block->prepare_attributes_for_render( $attributes ) ) ) ) );
	}
}
