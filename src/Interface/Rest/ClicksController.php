<?php
/**
 * The click beacon's route.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Application\Clicks\ClickInput;
use Affibloom\Application\Clicks\ClickTracker;
use Affibloom\Module;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * `POST /affibloom/v1/clicks` (F12 D2-D4; Task 33A): the reader's beacon from clicks.js, `{ p, h, l, b }` as text, at
 * most 1 KB. It is public, as a reader is anyone, and always answers 204 with no body and no cookie, whatever was sent
 * (edge cases 1, 2): a click that does not name a published product in a public post is ignored, and so are machines'
 * and repeated clicks (Task 33B).
 */
final class ClicksController implements Module {

	/**
	 * Builds the controller.
	 *
	 * @param ClickTracker $tracker Counts the click.
	 */
	public function __construct( private ClickTracker $tracker ) {}

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
			'/clicks',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'click' ),
				// Readers are anonymous; the body is read strictly and nothing about the reader is kept.
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Counts the click, or not; 204 either way.
	 *
	 * @param WP_REST_Request $request The beacon.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 */
	public function click( WP_REST_Request $request ): WP_REST_Response {
		$click = ClickInput::from_body( (string) $request->get_body() );
		if ( null !== $click ) {
			$this->tracker->track( $click, time(), (string) $request->get_header( 'user_agent' ), self::reader_address() );
		}

		return new WP_REST_Response( null, 204 );
	}

	/**
	 * The reader's address, as the repeat guard keys it: `REMOTE_ADDR`, through `affibloom_user_ip_address` so a site
	 * behind a proxy can give the forwarded one (Lasso's `lasso_get_user_ip_address`, classes/class-helper.php 156;
	 * Task GF5).
	 */
	public static function reader_address(): string {
		$address = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		return (string) apply_filters( 'affibloom_user_ip_address', $address );
	}
}
