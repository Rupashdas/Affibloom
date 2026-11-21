<?php
/**
 * Clicks repeated within seconds.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Clicks;

/**
 * A second click on the same product, post and button by the same reader within 30 seconds is not counted (F12 D6,
 * AC7; Task 33B). Nothing that identifies the reader is kept beyond those 30 seconds.
 */
interface RepeatGuard {

	/** How long a click holds back the same one, in seconds. */
	public const SECONDS = 30;

	/**
	 * Whether this is the first such click in the last 30 seconds; it is remembered for the next 30.
	 *
	 * @param string $key What makes two clicks the same (reader, product, post, button).
	 */
	public function first( string $key ): bool;
}
