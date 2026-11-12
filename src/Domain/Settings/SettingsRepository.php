<?php
/**
 * Where settings are kept.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Settings;

/**
 * Where settings are kept.
 */
interface SettingsRepository {

	/**
	 * The current settings.
	 */
	public function get(): Settings;

	/**
	 * Replaces the stored settings.
	 *
	 * @param Settings $settings New settings.
	 */
	public function save( Settings $settings ): void;
}
