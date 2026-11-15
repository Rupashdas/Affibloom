<?php
/**
 * The Amazon tab of Add A New Link: a product search and its selections.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Amazon;

use Affibloom\Domain\Amazon\Asin;
use Affibloom\Domain\Amazon\Marketplace;
use Affibloom\Domain\Amazon\SearchPage;
use Affibloom\Domain\Settings\SettingsRepository;

/**
 * The Amazon tab of Add A New Link (C1M; F18 D5, C1 D13): Lasso's Marketplace tab, searching the Creators API instead
 * of Lasso's cloud (R68-R70). A search asks the Default Tracking ID's store, ten items a page, and remembers what the
 * person was shown. A selection stores those items' data when the store has none for them (R71) and gives their
 * product links, so the add takes Amazon's name and image without asking Amazon again.
 */
final class AmazonSearchService {

	/**
	 * Builds the service.
	 *
	 * @param AmazonDataService  $data     Amazon's data on products: the credentials and the stored rows.
	 * @param AmazonCatalog      $catalog  The Creators API.
	 * @param SettingsRepository $settings The settings, for the Default Tracking ID's store.
	 * @param SearchResults      $results  What each person was shown.
	 */
	public function __construct(
		private AmazonDataService $data,
		private AmazonCatalog $catalog,
		private SettingsRepository $settings,
		private SearchResults $results
	) {}

	/**
	 * One page of a search. Empty keywords ask Amazon nothing and give an empty page (the tab's check that the search
	 * can be used).
	 *
	 * @param int    $user     The person's user ID.
	 * @param string $keywords What to search for.
	 * @param int    $page     The page, 1 to 10.
	 * @throws SearchRefused Without credentials, or when Amazon is unavailable or answers with an error.
	 */
	public function search( int $user, string $keywords, int $page ): SearchPage {
		$credentials = $this->data->usable();
		if ( null === $credentials ) {
			throw new SearchRefused( SearchRefused::NOT_CONFIGURED );
		}
		$keywords = trim( $keywords );
		if ( '' === $keywords ) {
			return new SearchPage( array(), 0, 1 );
		}

		try {
			$found = $this->catalog->search( $credentials, $keywords, max( 1, min( SearchPage::LAST, $page ) ), $this->country() );
		} catch ( CatalogError $error ) {
			// Lasso: HttpError and AuthFailed are "temporarily unavailable" (R68); a throttled request is the same here.
			throw new SearchRefused( CatalogError::API_ERROR === $error->kind ? SearchRefused::FAILED : SearchRefused::UNAVAILABLE );
		}
		$this->results->remember( $user, $found->items );

		return $found;
	}

	/**
	 * The product links of selected search results, after storing their data where the store has none (R71; stored
	 * data is fuller than a search result, so it is kept).
	 *
	 * @param int          $user  The person's user ID.
	 * @param list<string> $asins The selected ASINs.
	 * @return list<string> The links, "https://<store>/dp/<ASIN>", in the same order.
	 * @throws SearchRefused INVALID when an ASIN is not one of the person's results.
	 */
	public function selected( int $user, array $asins ): array {
		$country = $this->country();
		$items   = array();
		foreach ( $asins as $asin ) {
			$item = $this->results->find( $user, $asin );
			if ( null === $item || null === Asin::from( $item->asin ) ) {
				throw new SearchRefused( SearchRefused::INVALID );
			}
			$items[] = $item;
		}

		$urls = array();
		foreach ( $items as $item ) {
			$this->data->keep( $item, $country );
			$urls[] = 'https://' . Marketplace::host( $country ) . '/dp/' . strtoupper( $item->asin );
		}

		return $urls;
	}

	/**
	 * The store searched: the Default Tracking ID's (Lasso's get_country_for_creators_api), else `us`.
	 */
	private function country(): string {
		$country = $this->settings->get()->string( 'default_country' );

		return Marketplace::is_country( $country ) ? $country : Marketplace::DEFAULT_COUNTRY;
	}
}
