<?php
/**
 * Where the setup wizard's state is kept (Task ON6).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Shell;

/**
 * Where the setup wizard's state is kept (F38 N1, D13; Task ON6): '' (never due), SetupState::PENDING, ::SHOWN or ::DONE.
 */
interface SetupFlag {

	/**
	 * The state.
	 */
	public function get(): string;

	/**
	 * Stores the state.
	 *
	 * @param string $state One of SetupState's states.
	 */
	public function set( string $state ): void;
}
