<?php
/**
 * Settings stored in one autoloaded option, read once per request.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Domain\Settings\Settings;
use Affibloom\Domain\Settings\SettingsRepository;

/**
 * Settings stored in one autoloaded option, read once per request.
 */
final class OptionSettingsRepository implements SettingsRepository {

	public const OPTION = 'affibloom_settings';

	/**
	 * Settings already read in this request.
	 *
	 * @var Settings|null
	 */
	private ?Settings $loaded = null;

	/**
	 * The current settings.
	 */
	public function get(): Settings {
		$this->loaded ??= Settings::from_array( (array) get_option( self::OPTION, array() ) );
		return $this->loaded;
	}

	/**
	 * Replaces the stored settings. Autoloaded, because every page with a box reads them.
	 *
	 * @param Settings $settings New settings.
	 */
	public function save( Settings $settings ): void {
		update_option( self::OPTION, $settings->to_array(), true );
		$this->loaded = $settings;
	}
}
