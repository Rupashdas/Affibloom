<?php
namespace Affibloom\Tests\Unit\Fakes;

use Affibloom\Application\Amazon\SearchResults;
use Affibloom\Domain\Amazon\CatalogItem;

/**
 * The Amazon search results each person was shown, kept in memory.
 */
final class InMemorySearchResults implements SearchResults {
	/** @var array<int, array<string, CatalogItem>> By user, then ASIN. */
	public array $kept = array();

	public function remember( int $user, array $items ): void {
		foreach ( $items as $item ) {
			$this->kept[ $user ][ strtoupper( $item->asin ) ] = $item;
		}
	}

	public function find( int $user, string $asin ): ?CatalogItem {
		return $this->kept[ $user ][ strtoupper( $asin ) ] ?? null;
	}
}
