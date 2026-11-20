<?php
/**
 * The product page preview's route.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Application\Box\BoxBuilder;
use Affibloom\Application\Box\BoxFields;
use Affibloom\Application\Display\DisplayPresentation;
use Affibloom\Application\Display\DisplaySettingsService;
use Affibloom\Application\Products\ProductNotFound;
use Affibloom\Application\Products\ProductService;
use Affibloom\Domain\Amazon\AmazonProductRepository;
use Affibloom\Domain\Box\BoxOverrides;
use Affibloom\Domain\Display\DisplayType;
use Affibloom\Domain\Fields\FieldRepository;
use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Domain\Settings\SettingsRepository;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Interface\IconStyles;
use Affibloom\Interface\Front\BoxAssets;
use Affibloom\Interface\Front\BoxHtml;
use Affibloom\Module;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * `POST /affibloom/v1/products/{id}/preview` (F5 R100-R102, D11; F1 B2-B4): the product's real box drawn with the page's
 * unsaved form values (the payload Save sends), nothing saved; a theme change redraws it without saving (F1 D2). The
 * answer is what a preview frame needs: the HTML, the stylesheets, the `:root` block and the phone script.
 */
final class ProductPreviewController implements Module {

	/**
	 * Builds the controller.
	 *
	 * @param ProductService          $products The product use cases.
	 * @param ProductRepository       $catalog  The products, for their fields.
	 * @param FieldRepository         $fields   The field library.
	 * @param AmazonProductRepository $amazon   The stored Amazon data.
	 * @param DisplaySettingsService  $display  The Display settings.
	 * @param SettingsRepository      $settings The site's settings.
	 * @param BoxBuilder              $builder  The box engine.
	 * @param BoxHtml                 $html     The box's printer.
	 * @param BoxAssets               $assets   The box's stylesheets.
	 * @param IconStyles              $icons    The site's icon CSS.
	 */
	public function __construct(
		private readonly ProductService $products,
		private readonly ProductRepository $catalog,
		private readonly FieldRepository $fields,
		private readonly AmazonProductRepository $amazon,
		private readonly DisplaySettingsService $display,
		private readonly SettingsRepository $settings,
		private readonly BoxBuilder $builder,
		private readonly BoxHtml $html,
		private readonly BoxAssets $assets,
		private readonly IconStyles $icons,
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
			'/products/(?P<id>\d+)/preview',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'preview' ),
				'permission_callback' => static fn(): bool => current_user_can( Capabilities::PRODUCTS ),
			)
		);
	}

	/**
	 * The preview.
	 *
	 * @param WP_REST_Request $request The request: the product's ID and Save's payload.
	 */
	public function preview( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		try {
			$product = $this->products->preview( (int) $request['id'], array_intersect_key( $request->get_params(), ProductsController::field_args() ) );
		} catch ( ProductNotFound ) {
			return new WP_Error( 'affibloom_not_found', __( 'This product does not exist.', 'affibloom' ), array( 'status' => 404 ) );
		}

		$values  = $this->catalog->fields( (int) $product->id );
		$amazon  = ProductType::Amazon === $product->type && '' !== $product->asin ? $this->amazon->find( $product->asin, $product->amazon_country ) : null;
		$display = $this->display->current();
		$view    = $this->builder->build(
			$product,
			$display,
			$this->settings->get(),
			BoxOverrides::from_block( array( 'productId' => (int) $product->id ) ),
			0,
			null === $values ? null : new BoxFields( $values, $this->fields->load() ),
			$amazon
		);

		return new WP_REST_Response(
			array(
				'html'   => $this->html->render( $view ),
				'styles' => $this->assets->stylesheets( $display->advanced() ),
				'script' => $this->assets->phone_script(),
				'root'   => BoxAssets::head_style( DisplayPresentation::root( $display->config( DisplayType::single() ), $display->columns(), true ), $this->settings->get()->string( 'custom_css' ) ) . "\n" . $this->icons->preview_css(),
			)
		);
	}
}
