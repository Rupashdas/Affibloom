<?php
/**
 * The toast message after a redirect (?affibloom_notice=…).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

/**
 * The toast message after a redirect (?affibloom_notice=…). Shared by the Settings tabs, the Display screen and the
 * Dashboard.
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
			'link-added'   => __( 'Link added.', 'affibloom' ),
			// Lasso's warning when "Add A New Link" finds a product with the same link.
			'duplicate'    => __( 'This product already exists. Please update the Primary URL or add a new link.', 'affibloom' ),
			// Until Task 13 lists the products, the Dashboard says a bulk add finished (Lasso only reloads it).
			'bulk-added'   => __( 'Bulk add finished.', 'affibloom' ),
			default        => '',
		};
	}

	/**
	 * How the toast presents the message: "info" when nothing was done, else "success".
	 */
	public function kind(): string {
		$code = isset( $_GET['affibloom_notice'] ) ? sanitize_key( wp_unslash( $_GET['affibloom_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.

		return 'duplicate' === $code ? 'info' : 'success';
	}
}
