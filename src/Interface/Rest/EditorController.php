<?php
/**
 * The block editor's preview of a display.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Domain\Shortcode\DisplayRequest;
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
 */
final class EditorController implements Module {

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
