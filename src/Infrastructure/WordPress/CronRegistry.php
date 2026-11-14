<?php
/**
 * Every WP-Cron hook the plugin schedules: the one list deactivation clears.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Application\Schedules;

/**
 * Every WP-Cron hook the plugin schedules: the one list deactivation clears (F37 D1), as Lasso_Cron::CRONS is
 * Lasso's. Each cron module lists its hooks (BulkAddCron::HOOKS); Plugin hands them here.
 */
final class CronRegistry implements Schedules {

	/**
	 * The hook names.
	 *
	 * @var list<string>
	 */
	private array $hooks;

	/**
	 * Builds the registry.
	 *
	 * @param list<string> ...$hook_lists Each cron module's hooks.
	 */
	public function __construct( array ...$hook_lists ) {
		$this->hooks = array_values( array_unique( array_merge( ...$hook_lists ) ) );
	}

	/**
	 * The hook names, for checks that compare them with what is scheduled.
	 *
	 * @return list<string>
	 */
	public function hooks(): array {
		return $this->hooks;
	}

	/**
	 * Removes every scheduled event of every listed hook, whatever its arguments.
	 */
	public function clear(): void {
		foreach ( $this->hooks as $hook ) {
			wp_clear_scheduled_hook( $hook );
		}
	}
}
