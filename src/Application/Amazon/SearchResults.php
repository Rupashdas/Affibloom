<?php
/**
 * The Amazon search results a person was shown.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Amazon;

use Affibloom\Domain\Amazon\CatalogItem;

/**
 * The Amazon search results a person was shown (C1M), kept for a while so a selection is added with the data Amazon gave
 * (R71) and not with what the browser sends back.
 */
interface SearchResults {

	/**
	 * Keeps a page's items with the ones the person was shown before.
	 *
	 * @param int               $user  The person's user ID.
	 * @param list<CatalogItem> $items The page's items.
	 */
	public function remember( int $user, array $items ): void;

	/**
	 * A result the person was shown, or null.
	 *
	 * @param int    $user The person's user ID.
	 * @param string $asin The item's ASIN, any case.
	 */
	public function find( int $user, string $asin ): ?CatalogItem;
}
