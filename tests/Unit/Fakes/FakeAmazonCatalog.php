<?php
namespace Affibloom\Tests\Unit\Fakes;

use Affibloom\Application\Amazon\AmazonCatalog;
use Affibloom\Application\Amazon\CatalogError;
use Affibloom\Domain\Amazon\Asin;
use Affibloom\Domain\Amazon\CatalogItem;
use Affibloom\Domain\Amazon\Credentials;

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

	/** @var list<list<string>|null> The resources raw() was asked for. */
	public array $raw = array();

	public function raw( Credentials $credentials, Asin $asin, string $country, ?array $resources ): array {
		$this->raw[] = $resources;
		return array( 'status' => 200, 'body' => '{}' );
	}
}
