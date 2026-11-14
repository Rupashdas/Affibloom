<?php
/**
 * Whether WordPress lists an update of Affibloom.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Shell;

/**
 * Whether WordPress lists an update of Affibloom that the current user may install, and the link that starts it.
 */
interface UpdateSource {

	/**
	 * Whether an update is listed and the current user may update plugins.
	 */
	public function available(): bool;

	/**
	 * WordPress's own link that updates the plugin (with its nonce).
	 */
	public function update_url(): string;
}
