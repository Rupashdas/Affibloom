<?php
namespace Affibloom\Tests\Unit\Fakes;

use Affibloom\Application\Amazon\AmazonCatalog;
use Affibloom\Application\Amazon\CatalogError;
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
			throw new CatalogError( CatalogError::AUTH_FAILED, 'refused' );
		}
		if ( 'unavailable' === $this->answer ) {
			throw new CatalogError( CatalogError::HTTP_ERROR, 'down' );
		}
	}
}
