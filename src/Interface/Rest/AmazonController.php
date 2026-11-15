<?php
/**
 * REST routes for Amazon: affibloom/v1/amazon/… (C1).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Application\Amazon\AmazonDataService;
use Affibloom\Application\Amazon\BulkSyncRefused;
use Affibloom\Application\Amazon\BulkSyncService;
use Affibloom\Application\Amazon\CredentialsNotValidated;
use Affibloom\Application\Amazon\CredentialsService;
use Affibloom\Application\Amazon\RefreshFailed;
use Affibloom\Application\Amazon\SyncOutcome;
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
 * the card's new state. Its answers never carry the Secret or the Credential ID (AC5). The second is the product page's
 * Refresh (C1D; Lasso's upload_thumbnail for an Amazon product, R35). The last three are the bulk sync banner's: status,
 * start and dismiss (C1I; Lasso's lasso_bulk_sync_amazon_* AJAX actions, R42).
 */
final class AmazonController implements Module {

	/**
	 * Builds the controller.
	 *
	 * @param CredentialsService $credentials The card's use cases.
	 * @param AmazonDataService  $data        Amazon's data on products (Refresh).
	 * @param BulkSyncService    $sync        Bulk Amazon sync (the banner's routes, C1I).
	 */
	public function __construct( private CredentialsService $credentials, private AmazonDataService $data, private BulkSyncService $sync ) {}

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

		register_rest_route(
			ProductsController::NAMESPACE,
			'/products/(?P<id>\d+)/amazon/refresh',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'refresh' ),
				'permission_callback' => array( $this, 'can_edit_products' ),
				'args'                => array(
					'id' => array(
						'type'     => 'integer',
						'required' => true,
					),
				),
			)
		);

		// The bulk sync banner (C1I; Lasso's lasso_bulk_sync_amazon_* AJAX actions, R42).
		register_rest_route(
			ProductsController::NAMESPACE,
			'/amazon/bulk-sync',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'bulk_sync_status' ),
					'permission_callback' => array( $this, 'can_edit_products' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'bulk_sync_start' ),
					'permission_callback' => array( $this, 'can_edit_products' ),
				),
			)
		);
		register_rest_route(
			ProductsController::NAMESPACE,
			'/amazon/bulk-sync/dismiss',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'bulk_sync_dismiss' ),
				'permission_callback' => array( $this, 'can_edit_products' ),
			)
		);
	}

	/**
	 * GET /amazon/bulk-sync: how the last run stands (Lasso's _status).
	 */
	public function bulk_sync_status(): WP_REST_Response {
		return new WP_REST_Response( array( 'data' => self::sync_status( $this->sync->status() ) ) );
	}

	/**
	 * POST /amazon/bulk-sync: 202 with the new run's status; 409 while one runs or without credentials; 400 when nothing
	 * is pending, in Lasso's words (R42).
	 */
	public function bulk_sync_start(): WP_REST_Response|WP_Error {
		try {
			$status = $this->sync->start();
		} catch ( BulkSyncRefused $refused ) {
			[ $code, $message ] = match ( $refused->reason ) {
				BulkSyncRefused::RUNNING => array( 409, __( 'Bulk Amazon sync is already running.', 'affibloom' ) ),
				BulkSyncRefused::NOTHING => array( 400, __( 'No Amazon links need syncing.', 'affibloom' ) ),
				default                  => array( 409, __( 'Needs the Amazon API', 'affibloom' ) ),
			};
			return new WP_Error( 'affibloom_bulk_sync_' . $refused->reason, $message, array( 'status' => $code ) );
		}

		return new WP_REST_Response( array( 'data' => self::sync_status( $status ) ), 202 );
	}

	/**
	 * POST /amazon/bulk-sync/dismiss: 204; the banner stays hidden for this person until the pending set changes.
	 */
	public function bulk_sync_dismiss(): WP_REST_Response {
		$this->sync->dismiss( get_current_user_id() );

		return new WP_REST_Response( null, 204 );
	}

	/**
	 * A run's status for the banner's script, its failures in Lasso's words (R44).
	 *
	 * @param array{running: bool, percent: int, total: int, counts: array{succeeded: int, skipped: int, failed: int}, failures: list<array{id: int, reason: string}>, completed: bool, pending: int} $status From BulkSyncService.
	 * @return array<string, mixed>
	 */
	private static function sync_status( array $status ): array {
		$status['failures'] = array_map(
			static fn( array $failure ): array => array(
				'id'      => $failure['id'],
				'message' => self::sync_reason( $failure['reason'] ),
			),
			$status['failures']
		);

		return $status;
	}

	/**
	 * Lasso's words for why a product did not sync (sync_amazon_metadata_for_post; "Lasso" left out, D16).
	 *
	 * @param string $reason A SyncOutcome reason.
	 */
	private static function sync_reason( string $reason ): string {
		return match ( $reason ) {
			SyncOutcome::INVALID    => __( 'Invalid product ID.', 'affibloom' ),
			SyncOutcome::UNRESOLVED => __( 'Could not resolve Amazon ASIN.', 'affibloom' ),
			SyncOutcome::NOT_FOUND  => __( 'Amazon product not found.', 'affibloom' ),
			SyncOutcome::INCOMPLETE => __( 'Amazon sync returned incomplete product data.', 'affibloom' ),
			SyncOutcome::UNVERIFIED => __( 'Amazon sync failed to verify persisted product data.', 'affibloom' ),
			default                 => __( 'Amazon sync failed.', 'affibloom' ),
		};
	}

	/**
	 * Only people who change settings may validate credentials (Lasso: manage_options).
	 */
	public function can_change_settings(): bool {
		return current_user_can( Capabilities::SETTINGS );
	}

	/**
	 * Only people who edit products may refresh one (the product page's capability).
	 */
	public function can_edit_products(): bool {
		return current_user_can( Capabilities::PRODUCTS );
	}

	/**
	 * POST /products/{id}/amazon/refresh: 200 with the product's name and image link and the stored price, stock and
	 * time; 404 no such product; 422 not an Amazon product with an ASIN; 409 no credentials; 502 Amazon failed or gave
	 * no image, with Lasso's message.
	 *
	 * @param WP_REST_Request $request The product's ID.
	 * @phpstan-param WP_REST_Request<array{id: int}> $request
	 */
	public function refresh( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		try {
			[ 'product' => $product, 'data' => $data, 'renamed' => $renamed ] = $this->data->refresh( (int) $request['id'] );
		} catch ( RefreshFailed $refused ) {
			return self::refresh_error( $refused );
		}

		return new WP_REST_Response(
			array(
				'data' => array(
					'name'         => $product->name,
					'renamed'      => $renamed,
					'image_url'    => $product->image_url,
					'price'        => $data->price,
					'out_of_stock' => $data->out_of_stock,
					'last_updated' => $data->last_updated,
				),
			)
		);
	}

	/**
	 * The error for a refused Refresh, in Lasso's words where it has them (R35).
	 *
	 * @param RefreshFailed $refused Why.
	 */
	private static function refresh_error( RefreshFailed $refused ): WP_Error {
		[ $status, $message ] = match ( $refused->reason ) {
			RefreshFailed::NOT_FOUND      => array( 404, __( 'This product no longer exists.', 'affibloom' ) ),
			RefreshFailed::NOT_AMAZON     => array( 422, __( 'Refresh is for Amazon products with an ASIN.', 'affibloom' ) ),
			RefreshFailed::NOT_CONFIGURED => array( 409, __( 'Needs the Amazon API', 'affibloom' ) ),
			/* translators: %s: Amazon's error code, such as NotFound. */
			RefreshFailed::FAILED         => array( 502, sprintf( __( 'Amazon fetch failed (%s).', 'affibloom' ), $refused->kind ) ),
			default                       => array( 502, __( 'Could not fetch Amazon product image.', 'affibloom' ) ),
		};

		return new WP_Error( 'affibloom_' . $refused->reason, $message, array( 'status' => $status ) );
	}

	/**
	 * POST /amazon/validate: 200 with Lasso's success message and the state "validated"; 422 with the missing fields;
	 * 401 when Amazon refused them (kept, marked refused: C1Q0); 503 when Amazon was unavailable (nothing changed).
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
