<?php
/**
 * The link index rebuild's state in an option.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Domain\LinkIndex\RebuildState;
use Affibloom\Domain\LinkIndex\RebuildStateRepository;

/**
 * Option `affibloom_link_index_state` (F21 analysis, "Data"), not autoloaded: it is read by the cron run, the Settings
 * card and its poll only.
 */
final class OptionLinkIndexState implements RebuildStateRepository {

	public const OPTION = 'affibloom_link_index_state';

	/**
	 * The stored state; the idle state when there is none.
	 */
	public function get(): RebuildState {
		wp_cache_delete( self::OPTION, 'options' );

		return RebuildState::from_array( get_option( self::OPTION ) );
	}

	/**
	 * Stores the state.
	 *
	 * @param RebuildState $state The state.
	 */
	public function save( RebuildState $state ): void {
		update_option( self::OPTION, $state->to_array(), false );
	}
}
