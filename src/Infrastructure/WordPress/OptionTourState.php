<?php
/**
 * The Dashboard tour's state in an option (Task ON8).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Application\Shell\TourState;

/**
 * The Dashboard tour's state in `affibloom_tour_done` (F38 R62; Task ON8), '1' once ended; Phase M's 26M copies Lasso's
 * `lasso_dashboard_tour_done` into it. Autoloaded: the Dashboard reads it on every load.
 */
final class OptionTourState implements TourState {

	/** The option. */
	public const OPTION = 'affibloom_tour_done';

	/**
	 * Whether the tour was ended.
	 */
	public function done(): bool {
		return '1' === (string) get_option( self::OPTION, '' );
	}

	/**
	 * Ends the tour for every user.
	 */
	public function finish(): void {
		update_option( self::OPTION, '1', true );
	}
}
