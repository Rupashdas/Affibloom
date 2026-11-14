<?php
/**
 * The numbered schema and data steps, gated by affibloom_db_version.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\Schema;

use Affibloom\Application\Upgrade\Migrations;
use Affibloom\Application\Upgrade\StepFailed;
use Throwable;

/**
 * The numbered schema and data steps, gated by affibloom_db_version (F37 D2; Lasso's lasso_version checks in
 * Update_DB). Each step is recorded as soon as it finishes, so a failed step is retried on the next run and the steps
 * before it are not run again.
 */
final class Migrator implements Migrations {

	public const DB_VERSION = 'affibloom_db_version';

	/**
	 * The steps, by number, in order.
	 *
	 * @var array<int, callable(): void>
	 */
	private array $steps;

	/**
	 * Builds the migrator.
	 *
	 * @param array<int, callable(): void> $steps Each step by its number (1, 2, ...). A step never changes its number.
	 */
	public function __construct( array $steps ) {
		ksort( $steps );
		$this->steps = $steps;
	}

	/**
	 * Whether a step after the one the site has reached exists.
	 */
	public function pending(): bool {
		return array() !== $this->steps && array_key_last( $this->steps ) > $this->reached();
	}

	/**
	 * Runs every pending step in order, recording each one as it finishes.
	 *
	 * @return list<int> The steps that ran.
	 * @throws StepFailed When a step fails; the steps before it stay recorded.
	 */
	public function run(): array {
		$reached = $this->reached();
		$ran     = array();

		foreach ( $this->steps as $number => $step ) {
			if ( $number <= $reached ) {
				continue;
			}
			try {
				$step();
			} catch ( Throwable $error ) {
				throw new StepFailed( $number, $error ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- escaped where it is printed (UpgradeHooks).
			}
			update_option( self::DB_VERSION, $number );
			$ran[] = $number;
		}

		return $ran;
	}

	/**
	 * The last step this site finished; 0 on a new install.
	 */
	private function reached(): int {
		return (int) get_option( self::DB_VERSION, 0 );
	}
}
