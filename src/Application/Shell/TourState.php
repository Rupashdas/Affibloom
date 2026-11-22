<?php
/**
 * Whether the Dashboard tour has been ended (Task ON8).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Shell;

/**
 * Whether the Dashboard tour has been ended (F38 R59, R62, R63; Task ON8): one site-wide state, so one user's Done or
 * Skip ends it for every user, as Lasso's option does.
 */
interface TourState {

	/**
	 * Whether the tour was ended with Done or Skip.
	 */
	public function done(): bool;

	/**
	 * Ends the tour for every user.
	 */
	public function finish(): void;
}
