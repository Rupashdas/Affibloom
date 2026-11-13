<?php
/**
 * Where the icon set is kept.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Icons;

/**
 * Where the icon set is kept.
 */
interface IconRepository {

	/**
	 * The stored set, or null when none has been stored yet (the first read imports the site's icons).
	 */
	public function load(): ?IconSet;

	/**
	 * Stores the set.
	 *
	 * @param IconSet $set The set.
	 */
	public function save( IconSet $set ): void;
}
