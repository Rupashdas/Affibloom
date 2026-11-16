<?php
/**
 * The Display preview's route.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Application\Box\BoxBuilder;
use Affibloom\Application\Display\DisplayPresentation;
use Affibloom\Application\Display\DisplaySettingsService;
use Affibloom\Application\Display\PreviewProduct;
use Affibloom\Domain\Display\DisplayType;
use Affibloom\Domain\Settings\SettingsRepository;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Interface\Front\BoxAssets;
use Affibloom\Interface\Front\BoxHtml;
use Affibloom\Module;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * `POST /affibloom/v1/display-preview` (F5 R94-R97, D11; F16 AC2): the sample box drawn with the screen's unsaved Display
 * values, advanced rendering forced on (R96), as HTML with the stylesheets and the `:root` block its frame needs. Nothing
 * is saved and no global state changes. Until the group displays exist (DT2, DT4) Grid and List show their settings on
 * the Single box.
 */
final class DisplayPreviewController implements Module {

	/**
	 * Builds the controller.
	 *
	 * @param DisplaySettingsService $display  The Display settings.
	 * @param SettingsRepository     $settings The site's settings.
	 * @param BoxBuilder             $builder  The box engine.
	 * @param BoxHtml                $html     The box's printer.
	 * @param BoxAssets              $assets   The box's stylesheets.
	 * @param string                 $image    The sample's picture.
	 */
	public function __construct(
		private readonly DisplaySettingsService $display,
		private readonly SettingsRepository $settings,
		private readonly BoxBuilder $builder,
		private readonly BoxHtml $html,
		private readonly BoxAssets $assets,
		private readonly string $image,
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
			'/display-preview',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'preview' ),
				'permission_callback' => static fn(): bool => current_user_can( Capabilities::SETTINGS ),
				'args'                => array(
					'type' => array(
						'type'    => 'string',
						'enum'    => array( DisplayType::SINGLE, DisplayType::GRID, DisplayType::LIST ),
						'default' => DisplayType::SINGLE,
					),
				),
			)
		);
	}

	/**
	 * The preview.
	 *
	 * @param WP_REST_Request $request The request: `type` and the form's `display` values, by type (`display[<type>]`).
	 */
	public function preview( WP_REST_Request $request ): WP_REST_Response {
		$type  = DisplayType::from( (string) $request->get_param( 'type' ) );
		$input = $request->get_param( 'display' );
		$input = is_array( $input ) && isset( $input[ $type->key() ] ) && is_array( $input[ $type->key() ] ) ? $input[ $type->key() ] : array();
		$saved = $this->display->preview( $type, $input );

		// The edited type's settings drawn on the Single box, advanced rendering forced on (R96).
		$config  = $saved->config( $type );
		$preview = $saved->with_config( DisplayType::single(), $config )->with_advanced( true );
		$view    = $this->builder->build(
			PreviewProduct::product( $this->image ),
			$preview,
			$this->settings->get(),
			PreviewProduct::overrides( $config->theme()->name(), $type->is( DisplayType::grid() ) )
		);

		return new WP_REST_Response(
			array(
				'html'   => $this->html->render( $view ),
				'styles' => $this->assets->stylesheets( true ),
				'script' => $this->assets->phone_script(),
				'root'   => BoxAssets::head_style( DisplayPresentation::root( $config, $preview->columns(), true ), $this->settings->get()->string( 'custom_css' ) ),
			)
		);
	}
}
