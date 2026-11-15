<?php
/**
 * The Amazon search results each person was shown, in a transient per person.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Application\Amazon\SearchResults;
use Affibloom\Domain\Amazon\CatalogItem;

/**
 * The Amazon search results each person was shown (C1M), in the transient `affibloom_amazon_search_<user ID>` for an
 * hour: the latest 100 items by ASIN, enough for a bulk selection across the pages of a few searches.
 */
final class TransientSearchResults implements SearchResults {

	private const PREFIX = 'affibloom_amazon_search_';

	private const KEEP = 100;

	/**
	 * Keeps a page's items, newest last, dropping the oldest past 100.
	 *
	 * @param int               $user  The person's user ID.
	 * @param list<CatalogItem> $items The page's items.
	 */
	public function remember( int $user, array $items ): void {
		$kept = $this->kept( $user );
		foreach ( $items as $item ) {
			$asin = strtoupper( $item->asin );
			unset( $kept[ $asin ] );
			$kept[ $asin ] = $item;
		}
		set_transient( self::PREFIX . $user, array_slice( $kept, -self::KEEP, null, true ), HOUR_IN_SECONDS );
	}

	/**
	 * A result the person was shown, or null.
	 *
	 * @param int    $user The person's user ID.
	 * @param string $asin The item's ASIN.
	 */
	public function find( int $user, string $asin ): ?CatalogItem {
		return $this->kept( $user )[ strtoupper( $asin ) ] ?? null;
	}

	/**
	 * The kept items by ASIN; anything else stored is ignored.
	 *
	 * @param int $user The person's user ID.
	 * @return array<string, CatalogItem>
	 */
	private function kept( int $user ): array {
		$stored = get_transient( self::PREFIX . $user );

		return is_array( $stored ) ? array_filter( $stored, static fn( $item ): bool => $item instanceof CatalogItem ) : array();
	}
}
