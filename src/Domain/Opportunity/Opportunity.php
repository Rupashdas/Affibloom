<?php
/**
 * One link opportunity.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Opportunity;

use Affibloom\Domain\LinkIndex\ProductLocation;

/**
 * A row of Link Opportunities (F22 R8, R11, D4; Task O1): an untied link in a post, to the domain of a product the site
 * already has (the Suggestion). One per location and product, as Lasso lists them.
 */
final class Opportunity {

	/**
	 * Describes the row.
	 *
	 * @param ProductLocation $place        The link: its index row, how it is shown and its address.
	 * @param string          $domain       Its base domain.
	 * @param int             $host_id      The post it is in.
	 * @param string          $host_title   The post's title.
	 * @param string          $host_url     The post's permalink, as indexed.
	 * @param int             $product_id   The suggested product.
	 * @param string          $product_name Its name.
	 */
	public function __construct(
		public readonly ProductLocation $place,
		public readonly string $domain,
		public readonly int $host_id,
		public readonly string $host_title,
		public readonly string $host_url,
		public readonly int $product_id,
		public readonly string $product_name,
	) {}
}
