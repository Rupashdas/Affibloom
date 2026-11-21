<?php
/**
 * One affiliate network Lasso knows.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Monetize;

/**
 * One entry of Lasso's registry (F35 R2-R9, D2; Task C2A; resources/networks.php): how its links are recognised (a
 * named method or Lasso's regex, as written), its sub-ID keys, and, for the 32 networks Lasso detects automatically
 * (R7), the name stored for a detected link and where its IDs and deep link are read.
 */
final class Network {

	/** Impact's affiliate and advertiser IDs come from its path (R9). */
	public const IMPACT_PATH = '@impact';

	/** Partnerize's deep link is everything after `/destination:` (R9). */
	public const DESTINATION = '@destination';

	/**
	 * Builds the entry.
	 *
	 * @param string       $slug        Lasso's slug ("impact").
	 * @param string       $name        Lasso's name ("Impact").
	 * @param string       $method      'impact', 'amazon', 'post_affiliate_pro', or '' when the pattern decides.
	 * @param string       $pattern     Lasso's regex with its delimiters; '' for a method.
	 * @param list<string> $sub_ids     The network's sub-ID query keys (D8).
	 * @param string       $integration The name stored for a detected link; '' when Lasso never detects it (R8).
	 * @param list<string> $affiliate   Query keys of the affiliate ID, tried in order (or IMPACT_PATH).
	 * @param list<string> $advertiser  Query keys of the advertiser ID, tried in order (or IMPACT_PATH).
	 * @param string       $deep_link   Query key of the deep link, DESTINATION, or '' for none.
	 */
	public function __construct(
		public readonly string $slug,
		public readonly string $name,
		public readonly string $method,
		public readonly string $pattern,
		public readonly array $sub_ids,
		public readonly string $integration,
		public readonly array $affiliate,
		public readonly array $advertiser,
		public readonly string $deep_link,
	) {}

	/**
	 * Whether Lasso detects its links automatically (R7; the four of R8 and Amazon are not).
	 */
	public function is_detected(): bool {
		return '' !== $this->integration;
	}

	/**
	 * Whether it is Amazon Associates (R5), which detection leaves to the Amazon import.
	 */
	public function is_amazon(): bool {
		return 'amazon' === $this->method;
	}
}
