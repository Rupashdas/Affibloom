<?php
/**
 * Where the display configurations are kept.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Display;

/**
 * Where the display configurations are kept.
 */
interface DisplaySettingsRepository {

	/**
	 * The stored configurations, or the defaults.
	 */
	public function get(): DisplaySettings;

	/**
	 * Replaces the stored configurations.
	 *
	 * @param DisplaySettings $settings The configurations to store.
	 */
	public function save( DisplaySettings $settings ): void;
}
