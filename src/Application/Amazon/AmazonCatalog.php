<?php
/**
 * Amazon's Creators API, as the use cases need it.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Amazon;

use Affibloom\Domain\Amazon\Credentials;

/**
 * Amazon's Creators API, as the use cases need it (C1 D1: the only source of Amazon data; no cloud of Lasso's).
 */
interface AmazonCatalog {

	/**
	 * Validate's check (R21): asks Amazon for a new access token for the credentials, never a cached one.
	 *
	 * @param Credentials $credentials Configured credentials.
	 * @throws CatalogError When Amazon refuses them or does not answer.
	 */
	public function verify( Credentials $credentials ): void;
}
