<?php
/**
 * Saves an Amazon link's tag as the site's tracking ID, from the product page (Task 14D).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Application\Settings\SettingsService;
use Affibloom\Module;

/**
 * The product page's "Yes" (Lasso's amazon-url-detected notice and lasso_save_amazon_tracking_id): saves the link's tag
 * as the site's tracking ID, by the Amazon tab's rule, and goes back to the product's page with "Tracking ID Saved!".
 */
final class TrackingIdController implements Module {

	public const ACTION = 'affibloom_tracking_id';

	/**
	 * Builds the controller.
	 *
	 * @param SettingsService $settings The site's settings.
	 */
	public function __construct( private SettingsService $settings ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'admin_post_' . self::ACTION, array( $this, 'save' ) );
	}

	/**
	 * POST tracking_id, product: the tag to save and the page to go back to.
	 */
	public function save(): void {
		if ( ! current_user_can( Capabilities::SETTINGS ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to change these settings.', 'affibloom' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::ACTION );

		$tag     = isset( $_POST['tracking_id'] ) ? sanitize_text_field( wp_unslash( $_POST['tracking_id'] ) ) : '';
		$product = isset( $_POST['product'] ) ? absint( wp_unslash( $_POST['product'] ) ) : 0;
		$notice  = $this->settings->set_tracking_id( $tag ) ? 'tracking-saved' : 'tracking-refused';

		wp_safe_redirect( add_query_arg( 'affibloom_notice', $notice, ProductScreen::url( $product ) ) );
		exit;
	}
}
