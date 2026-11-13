<?php
/**
 * The icon set in the option affibloom_icons.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Domain\Icons\IconRepository;
use Affibloom\Domain\Icons\IconSet;

/**
 * The icon set in the option affibloom_icons (autoloaded: every page with a box or a picker reads it).
 */
final class OptionIconRepository implements IconRepository {

	public const OPTION = 'affibloom_icons';

	/**
	 * The stored set, or null when the option does not exist yet.
	 */
	public function load(): ?IconSet {
		$stored = get_option( self::OPTION, null );

		return is_array( $stored ) ? IconSet::from_array( $stored ) : null;
	}

	/**
	 * Stores the set.
	 *
	 * @param IconSet $set The set.
	 */
	public function save( IconSet $set ): void {
		update_option( self::OPTION, $set->to_array(), true );
	}
}
