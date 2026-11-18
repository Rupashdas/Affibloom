<?php
/**
 * Where the daily pass is kept.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\LinkHealth;

/**
 * The daily link check pass (Task 30A).
 */
interface CheckPassRepository {

	/**
	 * The stored pass; the empty pass when there is none.
	 */
	public function get(): CheckPass;

	/**
	 * Stores the pass.
	 *
	 * @param CheckPass $pass The pass.
	 */
	public function save( CheckPass $pass ): void;
}
