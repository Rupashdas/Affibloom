<?php
/**
 * What the extension API may do with Affibloom's settings.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Extension;

use Affibloom\Application\Settings\SettingsService;
use InvalidArgumentException;

/**
 * What the extension API may do with Affibloom's settings (Task 26C): read them, and save a tab through the Settings
 * screens' own rules, so a helper never writes the option itself.
 */
final class SettingsAccess {

	/**
	 * Builds the access.
	 *
	 * @param SettingsService $settings The Settings screens' rules.
	 */
	public function __construct( private SettingsService $settings ) {}

	/**
	 * Every setting's current value.
	 *
	 * @return array<string, mixed>
	 */
	public function values(): array {
		return $this->settings->current()->to_array();
	}

	/**
	 * Saves some of a tab's settings; a switch not given keeps its value.
	 *
	 * @param string               $tab    `general`, `display`, `amazon` or `import`.
	 * @param array<string, mixed> $values Setting => value.
	 * @return list<string> The settings refused; the rest are saved.
	 * @throws InvalidArgumentException When the tab is unknown.
	 */
	public function save( string $tab, array $values ): array {
		return $this->settings->import( $tab, $values );
	}
}
