<?php
/**
 * The display configurations in a WordPress option.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Domain\Display\DisplaySettings;
use Affibloom\Domain\Display\DisplaySettingsRepository;

/**
 * The display configurations in a WordPress option.
 *
 * They are read on every page that shows a box, so the option autoloads. Lasso keeps the same data in five
 * keys of its own settings option; Affibloom keeps it apart from the link defaults because its shape is
 * nested, while affibloom_settings is a flat map.
 */
final class OptionDisplaySettingsRepository implements DisplaySettingsRepository {

	public const OPTION = 'affibloom_display';

	/**
	 * The stored configurations, or the defaults.
	 */
	public function get(): DisplaySettings {
		$stored = get_option( self::OPTION, array() );

		return is_array( $stored ) ? DisplaySettings::from_array( $stored ) : DisplaySettings::defaults();
	}

	/**
	 * Replaces the stored configurations.
	 *
	 * @param DisplaySettings $settings The configurations to store.
	 */
	public function save( DisplaySettings $settings ): void {
		update_option( self::OPTION, $settings->to_array(), true );
	}
}
