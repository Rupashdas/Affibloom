<?php
/**
 * The toast message after a settings save.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

/**
 * The toast message after a settings save. Shared by the Settings tabs and the Display screen.
 */
final class SettingsNotice {

	/**
	 * The message for the notice code in the query, or '' when there is none.
	 */
	public function message(): string {
		$code = isset( $_GET['affibloom_notice'] ) ? sanitize_key( wp_unslash( $_GET['affibloom_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.

		return match ( $code ) {
			'saved'        => __( 'Settings saved.', 'affibloom' ),
			'partly-saved' => __( 'Some values were not valid and kept their previous setting. Everything else was saved.', 'affibloom' ),
			default        => '',
		};
	}
}
