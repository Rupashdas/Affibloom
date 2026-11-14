<?php
/**
 * REST routes for Amazon: affibloom/v1/amazon/… (C1).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Application\Amazon\CredentialsNotValidated;
use Affibloom\Application\Amazon\CredentialsService;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Interface\Messages;
use Affibloom\Module;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST routes for Amazon. The first is the Product Data API card's Validate (C1A; Lasso's
 * lasso_verify_amazon_creators_credentials, R19-R22): it checks the typed credentials and answers with the message and
 * the card's new state. Its answers never carry the Secret or the Credential ID (AC5).
 */
final class AmazonController implements Module {

	/**
	 * Builds the controller.
	 *
	 * @param CredentialsService $credentials The card's use cases.
	 */
	public function __construct( private CredentialsService $credentials ) {}

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
		$text = array(
			'type'    => 'string',
			'default' => '',
		);

		register_rest_route(
			ProductsController::NAMESPACE,
			'/amazon/validate',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'validate' ),
				'permission_callback' => array( $this, 'can_change_settings' ),
				'args'                => array(
					'credential_id' => $text,
					'secret'        => $text,
					'partner_tag'   => $text,
					'version'       => $text,
					'country'       => $text,
				),
			)
		);
	}

	/**
	 * Only people who change settings may validate credentials (Lasso: manage_options).
	 */
	public function can_change_settings(): bool {
		return current_user_can( Capabilities::SETTINGS );
	}

	/**
	 * POST /amazon/validate: 200 with Lasso's success message and the state "validated"; 422 with the missing fields;
	 * 401 when Amazon refused them (the stored ones are cleared); 503 when Amazon was unavailable (nothing changed).
	 *
	 * @param WP_REST_Request $request The four typed values and the selected country.
	 * @phpstan-param WP_REST_Request<array{credential_id: string, secret: string, partner_tag: string, version: string, country: string}> $request
	 */
	public function validate( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$input = array(
			'credential_id' => (string) $request['credential_id'],
			'secret'        => (string) $request['secret'],
			'partner_tag'   => sanitize_text_field( (string) $request['partner_tag'] ),
			'version'       => sanitize_text_field( (string) $request['version'] ),
		);

		try {
			$state = $this->credentials->validate( $input, sanitize_key( (string) $request['country'] ) );
		} catch ( CredentialsNotValidated $refused ) {
			return $this->refusal( $refused );
		}

		return new WP_REST_Response(
			array(
				'data' => array(
					'message' => Messages::for_reason( 'amazon_validated' ),
					'state'   => $state->value,
				),
			)
		);
	}

	/**
	 * The error for a refused Validate, with the card's state after it.
	 *
	 * @param CredentialsNotValidated $refused Why.
	 */
	private function refusal( CredentialsNotValidated $refused ): WP_Error {
		$state = $this->credentials->state()->value;

		if ( CredentialsNotValidated::MISSING === $refused->reason ) {
			$labels = array(
				'credential_id' => __( 'Credential ID', 'affibloom' ),
				'secret'        => __( 'Secret', 'affibloom' ),
				'version'       => __( 'Version', 'affibloom' ),
				'partner_tag'   => __( 'Partner tag', 'affibloom' ),
			);
			$names  = array_map( static fn( string $field ): string => $labels[ $field ], $refused->missing );

			return new WP_Error(
				'affibloom_amazon_missing',
				/* translators: %s: the empty fields' names, separated by commas. */
				sprintf( __( 'Missing required fields: %s', 'affibloom' ), implode( ', ', $names ) ),
				array(
					'status' => 422,
					'state'  => $state,
				)
			);
		}

		$unavailable = CredentialsNotValidated::UNAVAILABLE === $refused->reason;

		return new WP_Error(
			'affibloom_' . $refused->reason,
			Messages::for_reason( $refused->reason ),
			array(
				'status' => $unavailable ? 503 : 401,
				'state'  => $state,
			)
		);
	}
}
