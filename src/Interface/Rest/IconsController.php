<?php
/**
 * REST routes for the button icons: affibloom/v1/icons (F28, Task 14F; the one save behaviour, deviation 143).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Application\Icons\IconService;
use Affibloom\Domain\InvalidField;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Interface\Admin\IconChoices;
use Affibloom\Interface\Admin\Notice;
use Affibloom\Interface\Template;
use Affibloom\Module;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST routes for the "Button icons" card on Settings → Display. Add icon and Remove stay on the page, as every
 * settings save does: Add answers with the new icon's row for the card and its item for the pickers.
 */
final class IconsController implements Module {

	private const TYPES = array( 'image/svg+xml', 'image/png', 'image/webp' );

	/**
	 * Builds the controller.
	 *
	 * @param IconService $icons    The icon set.
	 * @param IconChoices $choices  The icons as the card and the pickers show them.
	 * @param Template    $template Draws the new icon's row and menu item.
	 */
	public function __construct( private IconService $icons, private IconChoices $choices, private Template $template ) {}

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
			ProductsController::NAMESPACE,
			'/icons',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'add' ),
				'permission_callback' => array( $this, 'can_change' ),
				'args'                => array(
					'name'       => array(
						'type'    => 'string',
						'default' => '',
					),
					'attachment' => array(
						'type'    => 'integer',
						'default' => 0,
					),
				),
			)
		);
		register_rest_route(
			ProductsController::NAMESPACE,
			'/icons/(?P<class>[a-z0-9_-]+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'remove' ),
				'permission_callback' => array( $this, 'can_change' ),
			)
		);
	}

	/**
	 * Only people who change settings may change the icon set.
	 */
	public function can_change(): bool {
		return current_user_can( Capabilities::SETTINGS );
	}

	/**
	 * POST /icons: adds an icon from a media library image (SVG, PNG or WebP) under a name.
	 *
	 * @param WP_REST_Request $request Request with "name" and "attachment".
	 * @phpstan-param WP_REST_Request<array{name: string, attachment: int}> $request
	 */
	public function add( WP_REST_Request $request ): WP_REST_Response {
		$name       = sanitize_text_field( (string) $request['name'] );
		$attachment = absint( $request['attachment'] );
		$url        = $attachment > 0 && current_user_can( 'read_post', $attachment ) && wp_attachment_is_image( $attachment )
			&& in_array( get_post_mime_type( $attachment ), self::TYPES, true ) ? (string) wp_get_attachment_url( $attachment ) : '';

		try {
			$icon = $this->icons->add( $name, esc_url_raw( $url ), $attachment );
		} catch ( InvalidField ) {
			return self::answer( 'icon-refused' );
		}

		foreach ( $this->choices->rows() as $row ) {
			if ( $row['icon']->css_class === $icon->css_class ) {
				return self::answer(
					'icon-added',
					array(
						'class' => $icon->css_class,
						'row'   => $this->template->fetch( 'admin/partials/icon-set-item', array( 'row' => $row ) ),
						'item'  => $this->template->fetch(
							'admin/partials/icon-menu-item',
							array(
								'row'     => $row,
								'checked' => false,
							)
						),
					)
				);
			}
		}

		return self::answer( 'icon-added' );
	}

	/**
	 * DELETE /icons/{class}: removes an icon added here; an icon from the site's CSS stays.
	 *
	 * @param WP_REST_Request $request Request with "class".
	 * @phpstan-param WP_REST_Request<array{class: string}> $request
	 */
	public function remove( WP_REST_Request $request ): WP_REST_Response {
		try {
			$this->icons->remove( sanitize_key( (string) $request['class'] ) );
		} catch ( InvalidField ) {
			return self::answer( 'icon-kept' );
		}

		return self::answer( 'icon-removed' );
	}

	/**
	 * The answer: the toast's words, whether they report a failure, and anything more.
	 *
	 * @param string               $code Notice code (Notice::text).
	 * @param array<string, mixed> $more More fields for the screen.
	 */
	private static function answer( string $code, array $more = array() ): WP_REST_Response {
		return new WP_REST_Response(
			array(
				'data' => array(
					'message' => Notice::text( $code ),
					'failed'  => Notice::failure( $code ),
				) + $more,
			)
		);
	}
}
