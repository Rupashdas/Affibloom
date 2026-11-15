<?php
namespace Affibloom\Tests\Unit\Fakes;

use Affibloom\Application\Amazon\AmazonCatalog;
use Affibloom\Application\Amazon\CatalogError;
use Affibloom\Domain\Amazon\Asin;
use Affibloom\Domain\Amazon\CatalogItem;
use Affibloom\Domain\Amazon\Credentials;
use Affibloom\Domain\Amazon\SearchPage;

/**
 * Amazon as a test says it answers: accepting ("ok"), refusing ("reject", AuthFailed) or unavailable ("unavailable",
 * HttpError).
 */
final class FakeAmazonCatalog implements AmazonCatalog {
	/** @var list<Credentials> */
	public array $verified = array();

	public function __construct( public string $answer = 'ok' ) {}

	public function verify( Credentials $credentials ): void {
		$this->verified[] = $credentials;
		if ( 'reject' === $this->answer ) {
			throw new CatalogError( CatalogError::AUTH_FAILED, 'refused', 'HTTP 401, invalid_client' );
		}
		if ( 'unavailable' === $this->answer ) {
			throw new CatalogError( CatalogError::HTTP_ERROR, 'down' );
		}
	}

	/**
	 * The item set in $items for the ASIN, or NotFound.
	 *
	 * @var array<string, CatalogItem>
	 */
	public array $items = array();

	/** Amazon does not answer item requests (HttpError). */
	public bool $failing = false;

	/** Item calls made. */
	public int $asked = 0;

	/** Answer item calls with a 429 and this wait, in seconds; 0 for none. */
	public int $throttled = 0;

	/** @var list<list<string>> The ASINs of each items() call. */
	public array $batches = array();

	public function items( Credentials $credentials, array $asins, string $country ): array {
		$this->batches[] = array_map( static fn( Asin $asin ): string => $asin->value, $asins );
		if ( $this->failing ) {
			throw new CatalogError( CatalogError::HTTP_ERROR, 'down' );
		}
		if ( $this->throttled > 0 ) {
			throw new CatalogError( CatalogError::THROTTLED, 'Request was denied due to request throttling.', '', $this->throttled );
		}
		$found = array();
		foreach ( $asins as $asin ) {
			if ( isset( $this->items[ $asin->value ] ) ) {
				$found[ $asin->value ] = $this->items[ $asin->value ];
			}
		}
		return $found;
	}

	public function item( Credentials $credentials, Asin $asin, string $country ): CatalogItem {
		++$this->asked;
		if ( $this->throttled > 0 ) {
			throw new CatalogError( CatalogError::THROTTLED, 'Request was denied due to request throttling.', '', $this->throttled );
		}
		if ( $this->failing ) {
			throw new CatalogError( CatalogError::HTTP_ERROR, 'down' );
		}
		if ( ! isset( $this->items[ $asin->value ] ) ) {
			throw new CatalogError( CatalogError::NOT_FOUND, 'Product not found.' );
		}
		return $this->items[ $asin->value ];
	}

	/** The page search() answers; an empty one when not set. */
	public ?SearchPage $search = null;

	/** Answer searches with an ApiError. */
	public bool $api_error = false;

	/** @var list<array{0: string, 1: int, 2: string}> The keywords, page and store of each search. */
	public array $searched = array();

	public function search( Credentials $credentials, string $keywords, int $page, string $country ): SearchPage {
		$this->searched[] = array( $keywords, $page, $country );
		if ( $this->failing ) {
			throw new CatalogError( CatalogError::HTTP_ERROR, 'down' );
		}
		if ( $this->api_error ) {
			throw new CatalogError( CatalogError::API_ERROR, 'bad request' );
		}
		return $this->search ?? new SearchPage( array(), 0, $page );
	}

	/** @var list<list<string>|null> The resources raw() was asked for. */
	public array $raw = array();

	public function raw( Credentials $credentials, Asin $asin, string $country, ?array $resources ): array {
		$this->raw[] = $resources;
		return array( 'status' => 200, 'body' => '{}' );
	}
}
