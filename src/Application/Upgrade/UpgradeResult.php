<?php
/**
 * What one upgrade run did.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Upgrade;

/**
 * What one upgrade run did: nothing was pending, another request held the lock, steps ran, or a step failed.
 */
final class UpgradeResult {

	public const NOTHING = 'nothing';
	public const LOCKED  = 'locked';
	public const DONE    = 'done';
	public const FAILED  = 'failed';

	/**
	 * Use the named constructors.
	 *
	 * @param string    $status      One of the constants.
	 * @param list<int> $steps       The steps that ran (DONE).
	 * @param int       $failed_step The step that failed (FAILED), else 0.
	 * @param string    $message     Its error (FAILED), else ''.
	 */
	private function __construct(
		public readonly string $status,
		public readonly array $steps = array(),
		public readonly int $failed_step = 0,
		public readonly string $message = ''
	) {}

	/**
	 * Nothing was pending.
	 */
	public static function nothing(): self {
		return new self( self::NOTHING );
	}

	/**
	 * Another request is running the upgrade.
	 */
	public static function locked(): self {
		return new self( self::LOCKED );
	}

	/**
	 * These steps ran.
	 *
	 * @param list<int> $steps The steps, in the order they ran.
	 */
	public static function done( array $steps ): self {
		return new self( self::DONE, $steps );
	}

	/**
	 * A step failed.
	 *
	 * @param int    $step    The step's number.
	 * @param string $message Its error.
	 */
	public static function failed( int $step, string $message ): self {
		return new self( self::FAILED, array(), $step, $message );
	}
}
