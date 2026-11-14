<?php
/**
 * The `wp affibloom upgrade` command.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Cli;

use Affibloom\Application\Upgrade\Upgrader;
use Affibloom\Application\Upgrade\UpgradeResult;
use Affibloom\Module;
use WP_CLI;

/**
 * `wp affibloom upgrade`: runs the pending schema and data steps now, as the first admin page after an update would.
 */
final class UpgradeCommand implements Module {

	/**
	 * Builds the command.
	 *
	 * @param Upgrader $upgrader The pending steps.
	 */
	public function __construct( private Upgrader $upgrader ) {}

	/**
	 * Adds the command when WP-CLI is running.
	 */
	public function register(): void {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'affibloom upgrade', $this );
		}
	}

	/**
	 * Runs the pending schema and data steps.
	 *
	 * ## EXAMPLES
	 *
	 *     wp affibloom upgrade
	 */
	public function __invoke(): void {
		[ $is_error, $message ] = self::report( $this->upgrader->run() );
		if ( $is_error ) {
			WP_CLI::error( $message );
		}
		WP_CLI::success( $message );
	}

	/**
	 * What the command says for a run, and whether that is an error (WP-CLI exits with 1 on an error).
	 *
	 * @param UpgradeResult $result The run.
	 * @return array{0: bool, 1: string}
	 */
	public static function report( UpgradeResult $result ): array {
		return match ( $result->status ) {
			UpgradeResult::DONE   => array(
				false,
				sprintf(
					'Ran %d %s (%s). The database is up to date.',
					count( $result->steps ),
					1 === count( $result->steps ) ? 'step' : 'steps',
					implode( ', ', $result->steps )
				),
			),
			UpgradeResult::LOCKED => array( true, 'Another upgrade is running. Try again in five minutes.' ),
			UpgradeResult::FAILED => array(
				true,
				sprintf(
					'Step %1$d failed: %2$s. Steps before it are kept; the next run starts at step %1$d.',
					$result->failed_step,
					$result->message
				),
			),
			default               => array( false, 'Nothing to do: the database is up to date.' ),
		};
	}
}
