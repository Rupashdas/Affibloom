<?php
/**
 * How a settings save ends: the browser's form goes to the next address, Save Changes' AJAX request is told it.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

/**
 * How a settings save ends. Lasso saves its settings by AJAX behind "Updating Your Site" and then loads the page again
 * (settings.js 2379-2464; F9 R41). The same handlers serve admin-post.php (the form without JavaScript) and
 * admin-ajax.php (settings-save.js): the first is redirected, the second gets the address as JSON and loads it itself.
 * Shared by SettingsController and DisplayController.
 */
final class SaveAnswer {

	/**
	 * Sends the reader on to an address this site built or validated, and stops.
	 *
	 * @param string $url An admin address.
	 */
	public static function go( string $url ): never {
		if ( wp_doing_ajax() ) {
			wp_send_json_success( array( 'redirect_to' => $url ) );
		}

		wp_safe_redirect( $url );
		exit;
	}
}
