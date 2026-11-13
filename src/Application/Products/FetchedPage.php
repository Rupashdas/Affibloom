<?php
/**
 * What a page fetch found.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Products;

/**
 * What a page fetch found.
 */
final class FetchedPage {

	/**
	 * Builds the result.
	 *
	 * @param int    $status    HTTP status of the last answer.
	 * @param string $final_url Where the redirects ended.
	 * @param string $body      The page, or '' when it was not read (a stop before Amazon).
	 */
	public function __construct(
		public readonly int $status,
		public readonly string $final_url,
		public readonly string $body,
	) {}
}
