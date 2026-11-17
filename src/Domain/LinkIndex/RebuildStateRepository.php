<?php
/**
 * Where the rebuild's state is kept.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\LinkIndex;

/**
 * The background rebuild's state (Task 35F).
 */
interface RebuildStateRepository {

	/**
	 * The stored state; the idle state when there is none.
	 */
	public function get(): RebuildState;

	/**
	 * Stores the state.
	 *
	 * @param RebuildState $state The state.
	 */
	public function save( RebuildState $state ): void;
}
