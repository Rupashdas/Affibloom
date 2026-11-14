<?php
/**
 * A schema or data step that did not finish.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Upgrade;

use RuntimeException;
use Throwable;

/**
 * A schema or data step that did not finish. Carries the step's number and the original error's message.
 */
final class StepFailed extends RuntimeException {

	/**
	 * Builds the error.
	 *
	 * @param int       $step     The step's number.
	 * @param Throwable $previous What the step threw.
	 */
	public function __construct( public readonly int $step, Throwable $previous ) {
		parent::__construct( $previous->getMessage(), 0, $previous );
	}
}
