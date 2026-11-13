<?php
/**
 * A Primary URL after the add flow's link rules.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Products;

use Affibloom\Domain\Amazon\AmazonUrl;
use Affibloom\Domain\Product\AffiliateUrl;

/**
 * What LinkNormaliser::primary() makes of a link: the link as typed (a bare ASIN made a store link, `https://` added),
 * the link to store (followed when it is an Amazon short link or a geni.us link not kept, then tagged when Amazon),
 * and the Amazon link it leads to, untagged, for its ASIN, store and starting name.
 */
final class NormalizedLink {

	/**
	 * Builds the result.
	 *
	 * @param AffiliateUrl   $typed  The link as typed, repaired.
	 * @param AffiliateUrl   $stored The link to store.
	 * @param AmazonUrl|null $amazon Where it leads, when that is Amazon.
	 */
	public function __construct(
		public readonly AffiliateUrl $typed,
		public readonly AffiliateUrl $stored,
		public readonly ?AmazonUrl $amazon,
	) {}
}
