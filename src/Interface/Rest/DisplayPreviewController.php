<?php
/**
 * The Display preview's route.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Application\Box\BoxBuilder;
use Affibloom\Application\Box\BoxView;
use Affibloom\Application\Box\GridView;
use Affibloom\Application\Box\ListView;
use Affibloom\Application\Display\DisplayPresentation;
use Affibloom\Application\Display\DisplaySettingsService;
use Affibloom\Application\Display\PreviewProduct;
use Affibloom\Domain\Box\ElementVisibility;
use Affibloom\Domain\Display\DisplaySettings;
use Affibloom\Domain\Display\DisplayType;
use Affibloom\Domain\Display\ListBullets;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Domain\Settings\SettingsRepository;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Interface\IconStyles;
use Affibloom\Interface\Front\BoxAssets;
use Affibloom\Interface\Front\BoxHtml;
use Affibloom\Interface\Front\DisplayHtml;
use Affibloom\Module;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * `POST /affibloom/v1/display-preview` (F5 R94-R97, D11; F16 AC2): the sample box drawn with the screen's unsaved Display
 * values, advanced rendering forced on (R96), as HTML with the stylesheets and the `:root` block its frame needs. Nothing
 * is saved and no global state changes. Grid and List draw Lasso's samples as the site's grid and list (R95).
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
	 * @param string                 $images   The folder of the samples' pictures, ending in `/`.
	 * @param IconStyles             $icons    The site's icon CSS.
	 * @param DisplayHtml            $displays The group displays' printer, for the Grid and List previews.
	 */
	public function __construct(
		private readonly DisplaySettingsService $display,
		private readonly SettingsRepository $settings,
		private readonly BoxBuilder $builder,
		private readonly BoxHtml $html,
		private readonly BoxAssets $assets,
		private readonly string $images,
		private readonly IconStyles $icons,
		private readonly DisplayHtml $displays,
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

		// The edited type's settings, advanced rendering forced on (R96): Grid as a grid, List as a list, Single one box.
		$config  = $saved->config( $type );
		$preview = $saved->with_config( DisplayType::single(), $config )->with_advanced( true );
		if ( $type->is( DisplayType::grid() ) ) {
			$html = $this->grid( $saved->with_advanced( true ), $this->unsaved( $request ) );
		} elseif ( $type->is( DisplayType::list() ) ) {
			$html = $this->list( $saved->with_advanced( true ) );
		} else {
			$html = $this->html->render(
				$this->builder->build(
					PreviewProduct::product( $this->images ),
					$preview,
					$this->settings->get(),
					PreviewProduct::overrides( $config->theme()->name(), false )
				)
			);
		}

		return new WP_REST_Response(
			array(
				'html'   => $html,
				'styles' => $this->assets->stylesheets( true ),
				'script' => $this->assets->phone_script(),
				'root'   => BoxAssets::head_style( DisplayPresentation::root( $config, $preview->columns(), true ), $this->settings->get()->string( 'custom_css' ) ) . "\n" . $this->icons->preview_css(),
			)
		);
	}

	/**
	 * The Grid preview (R95; Lasso's Html_Helper::render_view_by_display_type): one sample per column (at most 3) as the
	 * grid's cards in the Grid settings, in the site's grid wrapper, then the grid's one disclosure, as a group's grid
	 * prints them (ShortcodeRenderer::grid).
	 *
	 * @param DisplaySettings $display  The unsaved Display settings, advanced rendering on.
	 * @param Settings        $settings The site's settings with the screen's unsaved Disclosure values.
	 */
	private function grid( DisplaySettings $display, Settings $settings ): string {
		$cards = array_map(
			static fn( BoxView $card ): BoxView => $card->as_card(),
			$this->samples( $display, $settings, DisplayType::grid(), PreviewProduct::in_grid( $display->columns() ) )
		);
		$shows = $settings->bool( 'show_disclosure_grid' );
		$text  = $settings->string( 'disclosure_text' );

		return $this->displays->grid(
			new GridView(
				anchor: 'affibloom-preview-grid',
				classes: 'affibloom-grid-wrap affibloom-advanced',
				wrapper: DisplayPresentation::wrapper( $display->config( DisplayType::grid() ), $display->columns(), $shows, self::hidden( $display, DisplayType::grid() ) ),
				cards: $cards,
				disclosure: $shows && '' !== trim( $text ) ? $text : null,
			)
		);
	}

	/**
	 * The List preview (R95; Lasso's Html_Helper::render_view_by_display_type): the three samples as list cards in the
	 * List settings, in Lasso's numbered list, each with its own disclosure, as a group's list prints them
	 * (ShortcodeRenderer::list).
	 *
	 * @param DisplaySettings $display The unsaved Display settings, advanced rendering on.
	 */
	private function list( DisplaySettings $display ): string {
		$bullets = ListBullets::of( '' );

		return $this->displays->list(
			new ListView(
				anchor: 'affibloom-preview-list',
				tag: $bullets->tag,
				classes: $bullets->classes . ' affibloom-advanced',
				wrapper: DisplayPresentation::wrapper( $display->config( DisplayType::list() ), $display->columns(), true, self::hidden( $display, DisplayType::list() ) ),
				cards: $this->samples( $display, $this->settings->get(), DisplayType::list(), PreviewProduct::in_list() ),
			)
		);
	}

	/**
	 * The first `$count` samples, each built in a display type's settings, as a group's cards are.
	 *
	 * @param DisplaySettings $display  The unsaved Display settings, advanced rendering on.
	 * @param Settings        $settings The site's settings.
	 * @param DisplayType     $type     Grid or List.
	 * @param int             $count    How many samples.
	 * @return list<BoxView>
	 */
	private function samples( DisplaySettings $display, Settings $settings, DisplayType $type, int $count ): array {
		$theme = $display->config( $type )->theme()->name();
		$grid  = $type->is( DisplayType::grid() );

		$cards = array();
		for ( $sample = 0; $sample < $count; $sample++ ) {
			$cards[] = $this->builder->build(
				PreviewProduct::product( $this->images, $sample ),
				$display,
				$settings,
				PreviewProduct::overrides( $theme, $grid, $sample ),
				0,
				null,
				null,
				$type
			);
		}

		return $cards;
	}

	/**
	 * The elements a group's wrapper hides (F30 R16, R22): the type's switches and the sample's hides, without the price
	 * flag, which each card carries.
	 *
	 * @param DisplaySettings $display The Display settings.
	 * @param DisplayType     $type    Grid or List.
	 * @return list<string>
	 */
	private static function hidden( DisplaySettings $display, DisplayType $type ): array {
		$config    = $display->config( $type );
		$overrides = PreviewProduct::overrides( $config->theme()->name(), $type->is( DisplayType::grid() ) );

		return array_values( array_diff( ElementVisibility::of( $config->elements(), $overrides )->hidden(), array( 'price' ) ) );
	}

	/**
	 * The site's settings with the screen's unsaved Disclosure text and Disclosure in Grids, which the whole form posts
	 * as `settings[…]` (an unticked switch posts nothing), so the grid's disclosure follows them as Lasso's does.
	 *
	 * @param WP_REST_Request $request The request.
	 */
	private function unsaved( WP_REST_Request $request ): Settings {
		$settings = $this->settings->get();
		$posted   = $request->get_param( 'settings' );
		if ( ! is_array( $posted ) || ! isset( $posted['disclosure_text'] ) || ! is_string( $posted['disclosure_text'] ) ) {
			return $settings;
		}

		return $settings->with(
			array(
				'disclosure_text'      => sanitize_textarea_field( $posted['disclosure_text'] ),
				'show_disclosure_grid' => ! empty( $posted['show_disclosure_grid'] ),
			)
		);
	}
}
