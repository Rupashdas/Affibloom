<?php
/**
 * Amazon's catalogue, with its answers logged (Task TL3).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Logs;

use Affibloom\Application\Amazon\AmazonCatalog;
use Affibloom\Application\Amazon\CatalogError;
use Affibloom\Domain\Amazon\Asin;
use Affibloom\Domain\Amazon\CatalogItem;
use Affibloom\Domain\Amazon\Credentials;
use Affibloom\Domain\Amazon\SearchPage;
use Affibloom\Domain\Logs\LogType;
use Closure;

/**
 * Amazon's catalogue, with its answers logged (F37 R3, D3; Task TL3): each item Amazon gives goes to Amazon Processing
 * ("Each time your Amazon links are updated"), each error to Amazon Product API Errors; the answer or the error is
 * passed on as it came. It wraps the API client inside the quota guard, so the guard's own stops are not logged as
 * Amazon's errors. Credentials are never written.
 */
final class LoggedCatalog implements AmazonCatalog {

	/**
	 * Builds the catalogue.
	 *
	 * @param AmazonCatalog $inner The API client.
	 * @param Logger        $log   Where the lines go.
	 */
	public function __construct( private AmazonCatalog $inner, private Logger $log ) {}

	/**
	 * Checks the credentials.
	 *
	 * @param Credentials $credentials Configured credentials.
	 */
	public function verify( Credentials $credentials ): void {
		$this->logged( 'verify', fn() => $this->inner->verify( $credentials ) );
	}

	/**
	 * One item.
	 *
	 * @param Credentials $credentials Configured credentials.
	 * @param Asin        $asin        The ASIN.
	 * @param string      $country     The store's country code.
	 */
	public function item( Credentials $credentials, Asin $asin, string $country ): CatalogItem {
		$item = $this->logged( "item {$asin->value} ({$country})", fn() => $this->inner->item( $credentials, $asin, $country ) );
		$this->log->write( LogType::Amazon, "Updated {$asin->value} ({$country})." );

		return $item;
	}

	/**
	 * Up to ten items of one store.
	 *
	 * @param Credentials $credentials Configured credentials.
	 * @param list<Asin>  $asins       One to ten ASINs.
	 * @param string      $country     The store's country code.
	 * @return array<string, CatalogItem>
	 */
	public function items( Credentials $credentials, array $asins, string $country ): array {
		$asked = implode( ', ', array_map( static fn( Asin $asin ): string => $asin->value, $asins ) );
		$items = $this->logged( "items {$asked} ({$country})", fn() => $this->inner->items( $credentials, $asins, $country ) );
		$this->log->write( LogType::Amazon, sprintf( 'Updated %d of %d (%s): %s.', count( $items ), count( $asins ), $country, $asked ) );

		return $items;
	}

	/**
	 * One page of a search.
	 *
	 * @param Credentials $credentials Configured credentials.
	 * @param string      $keywords    What to search for.
	 * @param int         $page        The page, from 1.
	 * @param string      $country     The store's country code.
	 */
	public function search( Credentials $credentials, string $keywords, int $page, string $country ): SearchPage {
		return $this->logged( "search \"{$keywords}\" page {$page} ({$country})", fn() => $this->inner->search( $credentials, $keywords, $page, $country ) );
	}

	/**
	 * An item's raw answer.
	 *
	 * @param Credentials       $credentials Configured credentials.
	 * @param Asin              $asin        The ASIN.
	 * @param string            $country     The store's country code.
	 * @param list<string>|null $resources   The resources asked for, or null for all.
	 * @return array{status: int, body: string}
	 */
	public function raw( Credentials $credentials, Asin $asin, string $country, ?array $resources ): array {
		return $this->logged( "raw {$asin->value} ({$country})", fn() => $this->inner->raw( $credentials, $asin, $country, $resources ) );
	}

	/**
	 * Runs a call and logs its error, if any, before passing it on.
	 *
	 * @param string  $what What was asked, for the line.
	 * @param Closure $call The call.
	 * @return mixed What the call gave.
	 * @throws CatalogError The call's own error, after it is logged.
	 */
	private function logged( string $what, Closure $call ): mixed {
		try {
			return $call();
		} catch ( CatalogError $error ) {
			$this->log->write( LogType::AmazonApiError, "{$error->kind} on {$what}: {$error->getMessage()}" );
			throw $error;
		}
	}
}
