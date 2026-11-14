<?php
/**
 * The Amazon stores the Creators API serves, by country code.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Amazon;

/**
 * The Amazon stores the Creators API serves (Lasso_Amazon_Api::get_amazon_api_countries(), its two-letter keys, in
 * Lasso's order; C1 R10, R17): the country code, the store's name for the Default Tracking ID select, and the
 * marketplace host the API is asked with.
 */
final class Marketplace {

	/** Code => [ name, marketplace host ], in Lasso's order. */
	private const STORES = array(
		'us' => array( 'United States', 'www.amazon.com' ),
		'au' => array( 'Australia', 'www.amazon.com.au' ),
		'br' => array( 'Brazil', 'www.amazon.com.br' ),
		'ca' => array( 'Canada', 'www.amazon.ca' ),
		'cn' => array( 'China', 'www.amazon.cn' ),
		'fr' => array( 'France', 'www.amazon.fr' ),
		'de' => array( 'Germany', 'www.amazon.de' ),
		'in' => array( 'India', 'www.amazon.in' ),
		'it' => array( 'Italy', 'www.amazon.it' ),
		'jp' => array( 'Japan', 'www.amazon.co.jp' ),
		'mx' => array( 'Mexico', 'www.amazon.com.mx' ),
		'nl' => array( 'Netherlands', 'www.amazon.nl' ),
		'se' => array( 'Sweden', 'www.amazon.se' ),
		'sg' => array( 'Singapore', 'www.amazon.sg' ),
		'es' => array( 'Spain', 'www.amazon.es' ),
		'tr' => array( 'Turkey', 'www.amazon.com.tr' ),
		'ae' => array( 'United Arab Emirates', 'www.amazon.ae' ),
		'gb' => array( 'United Kingdom', 'www.amazon.co.uk' ),
	);

	/** Lasso's fallback store (R10). */
	public const DEFAULT_COUNTRY = 'us';

	/**
	 * Whether a code is one of the stores.
	 *
	 * @param string $country A country code.
	 */
	public static function is_country( string $country ): bool {
		return isset( self::STORES[ $country ] );
	}

	/**
	 * The stores for the Default Tracking ID select, code => name, in Lasso's order.
	 *
	 * @return array<string, string>
	 */
	public static function names(): array {
		return array_map( static fn( array $store ): string => $store[0], self::STORES );
	}

	/**
	 * The marketplace host for a country ("www.amazon.co.uk"), www.amazon.com for any other code (R10).
	 *
	 * @param string $country A country code.
	 */
	public static function host( string $country ): string {
		return self::STORES[ $country ][1] ?? self::STORES[ self::DEFAULT_COUNTRY ][1];
	}
}
