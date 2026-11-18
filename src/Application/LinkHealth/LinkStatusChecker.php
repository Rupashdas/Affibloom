<?php
/**
 * Asks a link for its HTTP status.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\LinkHealth;

use Affibloom\Domain\LinkHealth\CheckResult;

/**
 * Asks a link for its HTTP status (F11 D6, D9).
 */
interface LinkStatusChecker {

	/**
	 * What the link answered.
	 *
	 * @param string $url The link.
	 */
	public function status( string $url ): CheckResult;
}
