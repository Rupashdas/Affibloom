<?php
/**
 * What a network link says about itself.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Monetize;

/**
 * An affiliate link read by its network's rules (F35 R7, R9; Task C2A; Lasso's `lasso_auto_monetize` row): the network,
 * the affiliate and advertiser IDs, the deep link and its base domain ('' each when the network has no such rule or the
 * link carries none).
 */
final class DetectedLink {

	/**
	 * Builds the reading.
	 *
	 * @param Network $network       The network.
	 * @param string  $affiliate_id  The publisher's ID at the network.
	 * @param string  $advertiser_id The merchant's ID at the network.
	 * @param string  $deep_link     Where the link ends up.
	 * @param string  $domain        The deep link's base domain.
	 */
	public function __construct(
		public readonly Network $network,
		public readonly string $affiliate_id = '',
		public readonly string $advertiser_id = '',
		public readonly string $deep_link = '',
		public readonly string $domain = '',
	) {}
}
