<?php
/**
 * The WP-Cron event that runs the geni.us clean-up job.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Cron;

use Affibloom\Application\Products\GeniusCleanupService;
use Affibloom\Module;

/**
 * The WP-Cron event that runs the geni.us clean-up job (C1P; Lasso's Lasso_Process_Remove_Genius_Formatting): a single
 * event per run, chained, its argument the product ID the run goes on below.
 */
final class GeniusCleanupCron implements Module {

	public const HOOK = 'affibloom_genius_cleanup';

	/**
	 * Every hook this module schedules, for the CronRegistry deactivation clears.
	 *
	 * @var list<string>
	 */
	public const HOOKS = array( self::HOOK );

	/**
	 * Builds the module.
	 *
	 * @param GeniusCleanupService $cleanup The job.
	 */
	public function __construct( private GeniusCleanupService $cleanup ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( self::HOOK, array( $this, 'run' ) );
	}

	/**
	 * One run.
	 *
	 * @param mixed $before The event's argument: the product ID to go on below; 0 for the newest.
	 */
	public function run( mixed $before = 0 ): void {
		$this->cleanup->run( is_numeric( $before ) ? max( 0, (int) $before ) : 0 );
	}
}
