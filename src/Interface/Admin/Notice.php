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
			default        => '',
		};
	}
}
