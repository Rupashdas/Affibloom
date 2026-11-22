<?php
/**
 * Display settings kept in memory, for unit tests.
 *
 * @package Affibloom
 */

namespace Affibloom\Tests\Unit\Fakes;

use Affibloom\Domain\Display\DisplaySettings;
use Affibloom\Domain\Display\DisplaySettingsRepository;

/**
 * Display settings kept in memory, starting from Lasso's defaults.
 */
final class InMemoryDisplaySettingsRepository implements DisplaySettingsRepository {

	public DisplaySettings $stored;

	public function __construct() {
		$this->stored = DisplaySettings::defaults();
	}

	public function get(): DisplaySettings {
		return $this->stored;
	}

	public function save( DisplaySettings $settings ): void {
		$this->stored = $settings;
	}
}
