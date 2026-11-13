<?php
/**
 * The toast message after a redirect (?affibloom_notice=…).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

/**
 * The toast message after a redirect (?affibloom_notice=…). Shared by the Settings tabs and the Display screen.
 */
final class Notice {

	/**
	 * The message for the notice code in the query, or '' when there is none.
	 */
	public function message(): string {
		$code = isset( $_GET['affibloom_notice'] ) ? sanitize_key( wp_unslash( $_GET['affibloom_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.

		return match ( $code ) {
			'saved'        => __( 'Settings saved.', 'affibloom' ),
			'partly-saved' => __( 'Some values were not valid and kept their previous setting. Everything else was saved.', 'affibloom' ),
			'reset'        => __( 'Display settings were reset to their defaults.', 'affibloom' ),
			'icon-added'   => __( 'Icon added.', 'affibloom' ),
			'icon-removed' => __( 'Icon removed.', 'affibloom' ),
			'icon-refused' => __( 'The icon was not added. Give it a name of 1 to 40 characters and choose an SVG, PNG or WebP image.', 'affibloom' ),
			'icon-kept'    => __( "That icon comes from your site's CSS, so it stays.", 'affibloom' ),
			// The product page's tracking-ID offer (Task 14D): Lasso's "Tracking ID Saved!", and why one was not.
			'tracking-saved'   => __( 'Tracking ID Saved!', 'affibloom' ),
			'tracking-refused' => __( 'That was not a valid Tracking ID, so it was not saved.', 'affibloom' ),
			// A product page save that changed the product's type reloads the page (Task 15B, F1 D18); U6's wording.
			'product-saved'    => __( 'Product saved.', 'affibloom' ),
			default        => '',
		};
	}
}
