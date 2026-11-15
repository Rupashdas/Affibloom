<?php
/**
 * The scheduled Amazon refresh run in one option.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Domain\Amazon\RefreshRun;
use Affibloom\Domain\Amazon\RefreshRunRepository;

/**
 * The scheduled refresh run (C1Q4b) in the option `affibloom_amazon_refresh`, not autoloaded (read only by the job);
 * deleted when the run ends.
 */
final class OptionRefreshRunRepository implements RefreshRunRepository {

	public const OPTION = 'affibloom_amazon_refresh';

	/**
	 * The run in progress, or null.
	 */
	public function get(): ?RefreshRun {
		$stored = get_option( self::OPTION, null );
		if ( ! is_array( $stored ) || ! is_array( $stored['queue'] ?? null ) ) {
			return null;
		}

		$queue = array();
		foreach ( $stored['queue'] as $row ) {
			if ( is_array( $row ) && is_string( $row['asin'] ?? null ) && is_string( $row['country'] ?? null ) && is_string( $row['link'] ?? null ) ) {
				$queue[] = array(
					'asin'    => $row['asin'],
					'country' => $row['country'],
					'link'    => $row['link'],
				);
			}
		}

		return array() === $queue ? null : new RefreshRun( $queue, (int) ( $stored['started'] ?? 0 ) );
	}

	/**
	 * Keeps the run, or ends it (null).
	 *
	 * @param RefreshRun|null $run The run.
	 */
	public function save( ?RefreshRun $run ): void {
		if ( null === $run ) {
			delete_option( self::OPTION );
			return;
		}

		update_option(
			self::OPTION,
			array(
				'queue'   => $run->queue,
				'started' => $run->started,
			),
			false
		);
	}
}
