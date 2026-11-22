<?php
/**
 * The action that hands helper plugins the extension API.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Extension;

use Affibloom\Module;

/**
 * Fires `affibloom_ready` with the Api once, on plugins_loaded (Task LM1), when every plugin's file is loaded, so a
 * helper's hook is in place whichever loads first. A helper that never hears it is beside an Affibloom without the API.
 */
final class ReadyAction implements Module {

	/**
	 * Builds the module.
	 *
	 * @param Api $api What a helper gets.
	 */
	public function __construct( private Api $api ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'plugins_loaded', array( $this, 'fire' ) );
	}

	/**
	 * Hands the Api to the helpers.
	 */
	public function fire(): void {
		do_action( 'affibloom_ready', $this->api );
	}
}
