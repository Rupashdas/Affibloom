<?php
/**
 * The toast message after a redirect (?affibloom_notice=…).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

/**
 * The toast message after a redirect (?affibloom_notice=…). Shared by the Settings tabs, the Display screen, the
 * product page and the Dashboard.
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
			// After "This Product Already Exists" → Confirm, on the kept product's page (Lasso's words, url-details.php 2602).
			'repointed'        => __( 'Success! Locations now show this link.', 'affibloom' ),
			// On the Dashboard after Delete This Link (Task 17A, F1 D37); Lasso's redirect says nothing.
			'deleted'          => __( 'Product deleted.', 'affibloom' ),
			// The one-time Lasso import (the user, 2026-11-13); the counts ride along in the query.
			'lasso-imported'   => $this->imported(),
			'lasso-import-running' => __( 'An import is already running. Reload this page in a minute.', 'affibloom' ),
			default        => '',
		};
	}

	/**
	 * "Imported 254 products from Lasso.", with the records left out when there were any (?imported=…&skipped=…).
	 */
	private function imported(): string {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- display only.
		$imported = isset( $_GET['imported'] ) ? absint( wp_unslash( $_GET['imported'] ) ) : 0;
		$skipped  = isset( $_GET['skipped'] ) ? absint( wp_unslash( $_GET['skipped'] ) ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		/* translators: %s: how many products were imported. */
		$message = sprintf( _n( 'Imported %s product from Lasso.', 'Imported %s products from Lasso.', $imported, 'affibloom' ), number_format_i18n( $imported ) );
		if ( $skipped > 0 ) {
			/* translators: %s: how many Lasso records were left out. */
			$message .= ' ' . sprintf( _n( '%s Lasso record has no usable link and was left out.', '%s Lasso records have no usable link and were left out.', $skipped, 'affibloom' ), number_format_i18n( $skipped ) );
		}

		return $message;
	}
}
