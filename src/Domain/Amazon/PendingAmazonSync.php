<?php
/**
 * The Amazon products that still need Amazon's data.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Amazon;

/**
 * The Amazon products bulk sync is for (C1 R40, D12): published Amazon products whose ASIN has no stored row with a
 * name for its store, and Amazon links without an ASIN that a sync may still resolve (an `amzn.to` or `amzn.com` short
 * link, or a `/dp/`, `/gp/product/` or `/ASIN/` path).
 */
interface PendingAmazonSync {

	/**
	 * Their IDs, lowest first, as Lasso orders them.
	 *
	 * @param int $limit At most this many; 0 for all.
	 * @return list<int>
	 */
	public function ids( int $limit = 0 ): array;
}
