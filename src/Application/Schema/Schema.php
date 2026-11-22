<?php
/**
 * Affibloom's tables, to create or bring up to date (Task TL4).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Schema;

/**
 * Affibloom's tables, to create or bring up to date (F37 R10, D4; Task TL4).
 */
interface Schema {

	/**
	 * Creates every table, or adds what its definition has and the table lacks.
	 *
	 * @return array<string, list<string>> By table name (without the site's prefix): what changed, empty when nothing.
	 */
	public function recreate(): array;
}
