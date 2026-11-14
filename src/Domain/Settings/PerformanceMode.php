<?php
/**
 * Performance Mode: how hard background work may run (Settings → General, Task 9H).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Settings;

/**
 * Performance Mode: Power, Balanced, Light or Custom. A preset is three numbers (Lasso's general.php 669-707): the CPU
 * level background work waits below, the hours between cron runs, and how many cron jobs the browser fallback runs at
 * once. Custom has none: it keeps whatever is typed. The page applies a preset when the mode is changed; loading the
 * page never rewrites a stored value (F9 D13), so a site with Balanced and a CPU level of 80 keeps both.
 */
final class PerformanceMode {

	public const POWER    = 'power';
	public const BALANCED = 'balanced';
	public const LIGHT    = 'light';
	public const CUSTOM   = 'custom';

	/** The presets: the CPU level (%), the cron interval (hours) and the manual process limit. */
	private const PRESETS = array(
		self::POWER    => array(
			'cpu'      => 80,
			'interval' => 0,
			'limit'    => 2,
		),
		self::BALANCED => array(
			'cpu'      => 65,
			'interval' => 0,
			'limit'    => 2,
		),
		self::LIGHT    => array(
			'cpu'      => 50,
			'interval' => 1,
			'limit'    => 1,
		),
	);

	/**
	 * The modes, in the order the select shows them.
	 *
	 * @return list<string>
	 */
	public static function modes(): array {
		return array( self::POWER, self::BALANCED, self::LIGHT, self::CUSTOM );
	}

	/**
	 * Whether a value names a mode.
	 *
	 * @param string $mode The value.
	 */
	public static function is_mode( string $mode ): bool {
		return in_array( $mode, self::modes(), true );
	}

	/**
	 * A mode's preset, or null for Custom and for anything that is not a mode.
	 *
	 * @param string $mode The mode.
	 * @return array{cpu: int, interval: int, limit: int}|null
	 */
	public static function preset( string $mode ): ?array {
		return self::PRESETS[ $mode ] ?? null;
	}
}
