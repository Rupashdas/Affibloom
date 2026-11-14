<?php
/**
 * The notices of every provider, for the region under the header.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Shell;

use Affibloom\Domain\Shell\ShellNotice;
use Closure;
use Throwable;

/**
 * The notices of every provider, in the providers' order, for the region under the header (F38 D8, D10). A provider
 * that fails is skipped and reported, so the page still renders (edge case 1).
 */
final class NoticeBoard {

	/**
	 * Builds the board.
	 *
	 * @param list<NoticeProvider>     $providers In the order their notices show.
	 * @param Closure(Throwable): void $report    Told about a provider that failed.
	 */
	public function __construct( private array $providers, private Closure $report ) {}

	/**
	 * The notices for a page.
	 *
	 * @param string $page The Affibloom admin page slug being shown.
	 * @return list<ShellNotice>
	 */
	public function for_page( string $page ): array {
		$notices = array();
		foreach ( $this->providers as $provider ) {
			try {
				$notices = array_merge( $notices, $provider->notices( $page ) );
			} catch ( Throwable $error ) {
				( $this->report )( $error );
			}
		}

		return $notices;
	}
}
