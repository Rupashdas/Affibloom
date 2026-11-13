<?php
/**
 * Fetches a link's page for its title.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Products;

/**
 * Fetches a link's page for its title, following redirects. It never requests an Amazon page: a redirect into
 * Amazon stops there and comes back as that Amazon URL with no body (the Associates licence; overview, C1).
 */
interface PageFetcher {

	/**
	 * The page the link ends at, or null when there was no answer (no connection, a time-out, too many redirects).
	 *
	 * @param string $url An http(s) link.
	 */
	public function fetch( string $url ): ?FetchedPage;
}
