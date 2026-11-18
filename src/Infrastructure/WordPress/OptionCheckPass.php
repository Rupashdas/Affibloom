<?php
/**
 * The daily link check pass in an option.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Domain\LinkHealth\CheckPass;
use Affibloom\Domain\LinkHealth\CheckPassRepository;

/**
 * Option `affibloom_link_check` (F11 analysis, "Data"; D11), not autoloaded: read by the check's cron runs only.
 */
final class OptionCheckPass implements CheckPassRepository {

	public const OPTION = 'affibloom_link_check';

	/**
	 * The stored pass; the empty pass when there is none. Read past the object cache, since another request's batch
	 * may have just written it.
	 */
	public function get(): CheckPass {
		wp_cache_delete( self::OPTION, 'options' );
		return CheckPass::from_array( get_option( self::OPTION ) );
	}

	/**
	 * Stores the pass.
	 *
	 * @param CheckPass $pass The pass.
	 */
	public function save( CheckPass $pass ): void {
		update_option( self::OPTION, $pass->to_array(), false );
	}
}
