<?php
/**
 * A location's preview on the Locations tab.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Application\Display\DisplayPresentation;
use Affibloom\Application\Display\DisplaySettingsService;
use Affibloom\Application\Locations\LocationPreview;
use Affibloom\Domain\Display\DisplayType;
use Affibloom\Domain\Settings\SettingsRepository;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Interface\Front\BoxAssets;
use Affibloom\Interface\IconStyles;
use Affibloom\Module;
use WP_Error;
use WP_HTML_Tag_Processor;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * `GET /affibloom/v1/locations/{id}/preview` (F12 L10, D24; Task 36C). A link answers `{ kind: "link", html, host }`:
 * its paragraph escaped down to text and links, the location's link marked, every link opening in a new tab (L10); a
 * link the content no longer holds is rebuilt from the row. A display answers `{ kind: "display", html, styles,
 * script, root, host }`: drawn by Affibloom's shortcode or block, for the shared preview frame (affibloomBoxFrame).
 * Nothing is written.
 */
final class LocationPreviewController implements Module {

	/** What a paragraph keeps: its links and the inline marks around them. */
	private const KEPT = array(
		'a'      => array(
			'href'  => true,
			'class' => true,
			'title' => true,
		),
		'strong' => array(),
		'b'      => array(),
		'em'     => array(),
		'i'      => array(),
		'code'   => array(),
		'br'     => array(),
	);

	/**
	 * Builds the controller.
	 *
	 * @param LocationPreview        $preview  The location and its paragraph.
	 * @param DisplaySettingsService $display  The Display settings, for the frame's styles.
	 * @param SettingsRepository     $settings The site's settings: its custom CSS.
	 * @param BoxAssets              $assets   The box's stylesheets and phone script.
	 * @param IconStyles             $icons    The site's icon CSS.
	 */
	public function __construct(
		private LocationPreview $preview,
		private DisplaySettingsService $display,
		private SettingsRepository $settings,
		private BoxAssets $assets,
		private IconStyles $icons,
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
			'/locations/(?P<id>\d+)/preview',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'preview' ),
				'permission_callback' => static fn(): bool => current_user_can( Capabilities::PRODUCTS ),
				'args'                => array(
					'id' => array(
						'type'    => 'integer',
						'minimum' => 1,
					),
				),
			)
		);
	}

	/**
	 * The preview; 404 when the row is gone (the index was rebuilt meanwhile).
	 *
	 * @param WP_REST_Request $request Request with "id".
	 * @phpstan-param WP_REST_Request<array{id: int}> $request
	 */
	public function preview( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$found = $this->preview->of( (int) $request['id'] );
		if ( null === $found ) {
			return new WP_Error( 'affibloom_location_not_found', __( 'This location is no longer in the index. Reload the page.', 'affibloom' ), array( 'status' => 404 ) );
		}
		$location = $found['location'];
		$host     = get_the_title( $location->host_id );

		if ( $found['display'] ) {
			$display = $this->display->current();

			return new WP_REST_Response(
				array(
					'kind'   => 'display',
					'host'   => $host,
					'html'   => self::draw( $location->url ),
					'styles' => $this->assets->stylesheets( $display->advanced() ),
					'script' => $this->assets->phone_script(),
					'root'   => BoxAssets::head_style( DisplayPresentation::root( $display->config( DisplayType::single() ), $display->columns(), true ), $this->settings->get()->string( 'custom_css' ) ) . "\n" . $this->icons->preview_css(),
				)
			);
		}

		$html = null !== $found['snippet']
			? wp_kses( $found['snippet'], self::KEPT )
			: '<a class="affibloom-preview__hit" href="' . esc_url( $location->url ) . '">' . esc_html( $location->anchor_text ?? $location->url ) . '</a>';

		return new WP_REST_Response(
			array(
				'kind' => 'link',
				'host' => $host,
				'html' => self::new_tab( $html ),
			)
		);
	}

	/**
	 * A display drawn from its markup: a shortcode, or a block as the index writes it ("<name> <attributes JSON>").
	 *
	 * @param string $markup The display's markup.
	 */
	private static function draw( string $markup ): string {
		$markup = trim( $markup );
		if ( str_starts_with( $markup, '[' ) ) {
			return do_shortcode( $markup );
		}
		if ( 1 === preg_match( '#^([a-z0-9-]+/[a-z0-9-]+)(\s+\{.*\})?$#s', $markup, $block ) ) {
			return do_blocks( '<!-- wp:' . $block[1] . ( isset( $block[2] ) ? ' ' . trim( $block[2] ) : '' ) . ' /-->' );
		}

		return '<code>' . esc_html( $markup ) . '</code>';
	}

	/**
	 * Every link opens in a new tab (Lasso's preview, view-js.php 2740-2788).
	 *
	 * @param string $html The paragraph.
	 */
	private static function new_tab( string $html ): string {
		$tags = new WP_HTML_Tag_Processor( $html );
		while ( $tags->next_tag( array( 'tag_name' => 'a' ) ) ) {
			$tags->set_attribute( 'target', '_blank' );
			$tags->set_attribute( 'rel', 'noopener noreferrer' );
		}

		return $tags->get_updated_html();
	}
}
