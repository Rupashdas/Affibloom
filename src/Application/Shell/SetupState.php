<?php
/**
 * When the setup wizard opens by itself (Task ON6).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Shell;

/**
 * When the setup wizard opens by itself (F38 R46, N1, D13; Tasks ON6, ON7): once, on the first visit to an Affibloom
 * page after the first activation on a site with no Affibloom settings. Lasso's wizard was the licence gate's page
 * (not built, decision B1); a site moving from Lasso is the migrator's to judge (`affibloom_setup_wanted`, deviation 153).
 */
final class SetupState {

	/** Due: the next Affibloom page opens the wizard. */
	public const PENDING = 'pending';

	/** Opened by itself once; it does not open by itself again. */
	public const SHOWN = 'shown';

	/** Finished with Go to Dashboard. */
	public const DONE = 'done';

	/**
	 * Builds the state.
	 *
	 * @param SetupFlag $flag Where it is kept.
	 */
	public function __construct( private SetupFlag $flag ) {}

	/**
	 * At activation: due when the site has no Affibloom settings and the wizard was never due.
	 *
	 * @param bool $configured Whether Affibloom's settings exist already.
	 */
	public function on_activation( bool $configured ): void {
		if ( ! $configured && '' === $this->flag->get() ) {
			$this->flag->set( self::PENDING );
		}
	}

	/**
	 * Whether this visit should open the wizard; true once only, so it never opens by itself again.
	 */
	public function take_first_visit(): bool {
		if ( self::PENDING !== $this->flag->get() ) {
			return false;
		}
		$this->flag->set( self::SHOWN );

		return true;
	}

	/**
	 * The wizard was finished (Go to Dashboard).
	 */
	public function finish(): void {
		$this->flag->set( self::DONE );
	}
}
