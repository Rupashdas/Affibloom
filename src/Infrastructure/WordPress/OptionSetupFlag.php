<?php
/**
 * The setup wizard's state in an option (Task ON6).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Application\Shell\SetupFlag;

/**
 * The setup wizard's state in `affibloom_setup` (F38 D13; Task ON6), autoloaded: Affibloom's pages read it on every load.
 */
final class OptionSetupFlag implements SetupFlag {

	/** The option. */
	public const OPTION = 'affibloom_setup';

	/**
	 * The state.
	 */
	public function get(): string {
		return (string) get_option( self::OPTION, '' );
	}

	/**
	 * Stores the state.
	 *
	 * @param string $state The state.
	 */
	public function set( string $state ): void {
		update_option( self::OPTION, $state, true );
	}
}
