<?php
/**
 * The plugin's numbered schema and data steps.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Upgrade;

/**
 * The plugin's numbered schema and data steps.
 */
interface Migrations {

	/**
	 * Whether a step after the one the site has reached exists.
	 */
	public function pending(): bool;

	/**
	 * Runs every pending step in order, recording each one as it finishes.
	 *
	 * @return list<int> The steps that ran.
	 * @throws StepFailed When a step fails; the steps before it stay recorded.
	 */
	public function run(): array;
}
