<?php
namespace Affibloom\Tests\Unit\Fakes;

use Affibloom\Domain\Settings\Settings;
use Affibloom\Domain\Settings\SettingsRepository;

/**
 * Settings kept in memory, starting from the defaults with any changes given.
 */
final class InMemorySettingsRepository implements SettingsRepository {
	public Settings $stored;

	/**
	 * @param array<string, mixed> $changes Keys to change from the defaults.
	 */
	public function __construct( array $changes = array() ) {
		$this->stored = Settings::from_array( $changes );
	}

	public function get(): Settings {
		return $this->stored;
	}

	public function save( Settings $settings ): void {
		$this->stored = $settings;
	}
}
