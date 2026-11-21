<?php
/**
 * Amazon's domains, which are never link opportunities.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Opportunity;

/**
 * The Amazon domains Link Opportunities leaves out (F22 R8, D4): Lasso's 23 (`Lasso_Amazon_Api::get_domains()`)
 * without the two short-link hosts its query keeps (`amzn.com`, `amzn.to`; `get_amazon_domains_in_clause()`), so 21.
 * Amazon's own links are F35's (auto-monetization), not a product suggestion.
 */
final class AmazonDomains {

	/** The 21 domains. */
	public const EXCLUDED = array(
		'amazon.com',
		'amazon.ca',
		'amazon.co.uk',
		'amazon.com.au',
		'amazon.com.br',
		'amazon.com.mx',
		'amazon.fr',
		'amazon.de',
		'amazon.it',
		'amazon.in',
		'amazon.es',
		'amazon.cn',
		'amazon.co.jp',
		'amazon.nl',
		'amazon.se',
		'amazon.sg',
		'amazon.com.tr',
		'amazon.ae',
		'amzlink.to',
		'amazon-adsystem.com',
		'smile.amazon.com',
	);

	/**
	 * Whether a base domain is one of them.
	 *
	 * @param string $domain The base domain.
	 */
	public static function excludes( string $domain ): bool {
		return in_array( strtolower( $domain ), self::EXCLUDED, true );
	}
}
