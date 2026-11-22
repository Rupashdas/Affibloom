<?php
/**
 * REST routes for Settings: affibloom/v1/settings (Task 9D; the one save behaviour, deviation 143).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Application\Amazon\CredentialsService;
use Affibloom\Application\Display\DisplaySettingsService;
use Affibloom\Application\Products\GeniusCleanupService;
use Affibloom\Application\Settings\LinkDefaultsService;
use Affibloom\Application\Settings\SettingsService;
use Affibloom\Domain\Display\DisplayType;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Interface\Admin\Notice;
use Affibloom\Interface\Admin\SettingsScreen;
use Affibloom\Interface\Template;
use Affibloom\Module;
use InvalidArgumentException;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST routes for Settings. Every settings screen saves here and stays on the page, as the product, group and field
 * screens do (the user, 2026-11-16, deviation 143; Lasso reloads after its AJAX save): a tab of Settings, the Display
 * screen and its Reset Settings, and the product page's tracking-ID offer. The forms are sent as they are (multipart),
 * so their `settings[…]` and `display[…]` fields arrive as arrays. Each answer carries the toast's words; a save that
 * refused some values also names them, so the screen marks them.
 *
 * And Lasso's "Override All Links": the Update Link Settings dialog pushes a changed link default to every existing
 * product at once, before and apart from Save (F9 D7, R48).
 */
final class SettingsRestController implements Module {

	/**
	 * Builds the controller.
	 *
	 * @param LinkDefaultsService    $defaults    Pushes a link default to every product.
	 * @param SettingsService        $settings    Settings use cases.
	 * @param DisplaySettingsService $display     The Display screen's per-type model.
	 * @param CredentialsService     $credentials The Amazon tab's Product Data API card (C1A).
	 * @param GeniusCleanupService   $genius      The job "Keep Geni.us Formatting" queues and clears (C1P).
	 * @param Template               $template    Draws the Product Data API card again after an Amazon save.
	 */
	public function __construct(
		private LinkDefaultsService $defaults,
		private SettingsService $settings,
		private DisplaySettingsService $display,
		private CredentialsService $credentials,
		private GeniusCleanupService $genius,
		private Template $template
	) {}

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
			'/settings/link-defaults/apply',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'apply' ),
				'permission_callback' => array( $this, 'can_change' ),
				'args'                => array(
					'key'   => array(
						'type'     => 'string',
						'required' => true,
					),
					'value' => array(
						'type'     => 'boolean',
						'required' => true,
					),
				),
			)
		);
		register_rest_route(
			ProductsController::NAMESPACE,
			'/settings/tabs/(?P<tab>[a-z_]+)',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'save_tab' ),
				'permission_callback' => array( $this, 'can_change' ),
			)
		);
		register_rest_route(
			ProductsController::NAMESPACE,
			'/settings/display',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'save_display' ),
				'permission_callback' => array( $this, 'can_change' ),
			)
		);
		register_rest_route(
			ProductsController::NAMESPACE,
			'/settings/display/reset',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'reset_display' ),
				'permission_callback' => array( $this, 'can_change' ),
			)
		);
		register_rest_route(
			ProductsController::NAMESPACE,
			'/settings/tracking-id',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'save_tracking_id' ),
				'permission_callback' => array( $this, 'can_change' ),
				'args'                => array(
					'tracking_id' => array(
						'type'     => 'string',
						'required' => true,
					),
				),
			)
		);
	}

	/**
	 * Only people who change settings may save them or push one to every product.
	 */
	public function can_change(): bool {
		return current_user_can( Capabilities::SETTINGS );
	}

	/**
	 * POST /settings/link-defaults/apply: sets one switch on every product.
	 *
	 * @param WP_REST_Request $request Request with "key" and "value".
	 * @phpstan-param WP_REST_Request<array{key: string, value: bool}> $request
	 */
	public function apply( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		try {
			$count = $this->defaults->apply_to_all( (string) $request['key'], (bool) $request['value'] );
		} catch ( InvalidArgumentException $error ) {
			return new WP_Error( 'affibloom_unknown_default', $error->getMessage(), array( 'status' => 422 ) );
		}

		return new WP_REST_Response( array( 'data' => array( 'updated' => $count ) ) );
	}

	/**
	 * POST /settings/tabs/{tab}: saves one Settings tab (General, Amazon). Invalid values keep their old value and are
	 * named in `refused`. The Amazon tab also saves its Product Data API credentials, validated when they are not yet
	 * (C1 R23), and answers with its card drawn again from what is stored.
	 *
	 * @param WP_REST_Request $request Request with the tab (from the route) and the form's fields.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 */
	public function save_tab( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$tab = sanitize_key( (string) $request->get_param( 'tab' ) );
		// Display has its own route: its form edits two stores.
		if ( 'display' === $tab || ! $this->settings->saves( $tab ) ) {
			return new WP_Error( 'affibloom_unknown_tab', __( 'Unknown settings tab.', 'affibloom' ), array( 'status' => 404 ) );
		}

		// Each field is sanitised and validated by type in SettingsService.
		$input = self::posted( $request, 'settings' );
		// F37 R34 (Task TL6): who may use Affibloom, and whether deleting it removes its data, are the administrators'
		// to change; anyone else's save keeps them as stored, as Lasso drops them silently (a checkbox left out would be
		// saved as off, so the stored values are put in, not the fields taken out).
		if ( ! current_user_can( Capabilities::PERMISSION ) ) {
			$stored                            = $this->settings->current();
			$input['permission']               = $stored->string( 'permission' );
			$input['delete_data_on_uninstall'] = $stored->bool( 'delete_data_on_uninstall' ) ? '1' : '';
		}
		$keep_genius = $this->settings->current()->bool( 'keep_genius_formatting' );
		$refused     = $this->settings->update_tab( $tab, $input );
		if ( 'amazon' !== $tab ) {
			return self::answer( $refused ? 'partly-saved' : 'saved', $refused );
		}

		// Turned off: the job that re-saves geni.us products is queued; turned on: it is cleared (C1 R63).
		$this->genius->switched( $keep_genius, $this->settings->current()->bool( 'keep_genius_formatting' ) );
		// Saved after the tab, whose Default Tracking ID they are validated for.
		$unverified = $this->credentials->save( self::api_fields( self::posted( $request, 'amazon_api' ) ) );
		$code       = $refused ? 'partly-saved' : ( null === $unverified ? 'saved' : 'amazon-unverified-' . $unverified );

		return self::answer(
			$code,
			$refused,
			array(
				'api_card' => $this->template->fetch(
					'admin/partials/amazon-api-card',
					array(
						'settings' => $this->settings->current(),
						'api'      => SettingsScreen::api_card( $this->credentials ),
					)
				),
			)
		);
	}

	/**
	 * POST /settings/display: saves the Display screen, which edits two stores as Lasso's does: the per-type display
	 * model (every type the screen sends), and the site-wide display settings (disclosure, Show Price, Site Stripe,
	 * Custom CSS).
	 *
	 * @param WP_REST_Request $request Request with the form's fields.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 */
	public function save_display( WP_REST_Request $request ): WP_REST_Response {
		$flat = self::posted( $request, 'settings' );
		// Lasso's one "Disclosure in Lists & Tables" toggle writes both keys (display.php 676-677).
		$flat['show_disclosure_table'] = $flat['show_disclosure_list'] ?? null;

		// The screen holds every type and sends those it showed (`display[<type>][…]`), as Lasso saves them all at
		// once (deviation 144). Each value is cleaned by type in DisplaySettingsService and SettingsService.
		$display = self::posted( $request, 'display' );
		$refused = array();
		foreach ( DisplayType::all() as $type ) {
			if ( isset( $display[ $type->key() ] ) && is_array( $display[ $type->key() ] ) ) {
				$refused = array_merge( $refused, $this->display->update_type( $type, $display[ $type->key() ] ) );
			}
		}
		$refused = array_merge( $refused, $this->settings->update_tab( 'display', $flat ) );

		return self::answer( $refused ? 'partly-saved' : 'saved', array_values( array_unique( $refused ) ) );
	}

	/**
	 * POST /settings/display/reset: puts every Display setting back to Lasso's factory defaults, as its Reset Settings
	 * does. Every field of the screen changes, so the answer names the address that shows them; the screen loads it.
	 *
	 * @param WP_REST_Request $request Request with the display type shown.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 */
	public function reset_display( WP_REST_Request $request ): WP_REST_Response {
		$this->display->reset();
		$this->settings->reset_tab( 'display' );

		$type = DisplayType::from( sanitize_key( (string) $request->get_param( 'type' ) ) );

		return self::answer(
			'reset',
			array(),
			array(
				'reload' => add_query_arg(
					array(
						'page'             => SettingsScreen::SLUG,
						'tab'              => 'display',
						'type'             => $type->key(),
						'affibloom_notice' => 'reset',
					),
					admin_url( 'admin.php' )
				),
			)
		);
	}

	/**
	 * POST /settings/tracking-id: the product page's offer to save a link's tracking ID as the site's (Task 14D).
	 *
	 * @param WP_REST_Request $request Request with "tracking_id".
	 * @phpstan-param WP_REST_Request<array{tracking_id: string}> $request
	 */
	public function save_tracking_id( WP_REST_Request $request ): WP_REST_Response {
		$saved = $this->settings->set_tracking_id( sanitize_text_field( (string) $request['tracking_id'] ) );

		return self::answer( $saved ? 'tracking-saved' : 'tracking-refused', array() );
	}

	/**
	 * A save's answer: the toast's words and whether they report a failure, the refused keys, and anything more.
	 *
	 * @param string               $code    Notice code (Notice::text).
	 * @param list<string>         $refused Keys that kept their old value.
	 * @param array<string, mixed> $more    More fields for the screen.
	 */
	private static function answer( string $code, array $refused, array $more = array() ): WP_REST_Response {
		return new WP_REST_Response(
			array(
				'data' => array(
					'message' => Notice::text( $code ),
					'failed'  => Notice::failure( $code ),
					'refused' => $refused,
				) + $more,
			)
		);
	}

	/**
	 * One posted group of fields (`settings[…]`), as an array; anything else is no fields. WordPress has already
	 * unslashed the body.
	 *
	 * @param WP_REST_Request $request The request.
	 * @param string          $name    The group's name.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return array<string, mixed>
	 */
	private static function posted( WP_REST_Request $request, string $name ): array {
		$value = $request->get_param( $name );

		return is_array( $value ) ? $value : array();
	}

	/**
	 * The Product Data API card's four fields as posted (`amazon_api[…]`). The Credential ID and Secret are kept as typed
	 * (trimmed later), since any character may be part of a secret; the others are plain text.
	 *
	 * @param array<string, mixed> $posted The posted card.
	 * @return array<string, string>
	 */
	private static function api_fields( array $posted ): array {
		$text = static fn( string $key ): string => isset( $posted[ $key ] ) && is_string( $posted[ $key ] ) ? $posted[ $key ] : '';

		return array(
			'credential_id' => $text( 'credential_id' ),
			'secret'        => $text( 'secret' ),
			'partner_tag'   => sanitize_text_field( $text( 'partner_tag' ) ),
			'version'       => sanitize_text_field( $text( 'version' ) ),
		);
	}
}
