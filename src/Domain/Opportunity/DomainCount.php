<?php
/**
 * A domain the site links to, and how often.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Opportunity;

/**
 * A row of Domain Opportunities (F22 R17, R19; Task O2): a domain, how many untied text links point to it, and what
 * could monetize it: a product's name for the same domain, "Amazon" for one of Amazon's, or '' (no lightbulb).
 */
final class DomainCount {

	/**
	 * Describes the row.
	 *
	 * @param string $domain        The base domain.
	 * @param int    $links         How many links.
	 * @param string $monetize_with A product of that domain, "Amazon Associates", or ''.
	 */
	public function __construct(
		public readonly string $domain,
		public readonly int $links,
		public readonly string $monetize_with,
	) {}

	/**
	 * Whether the domain has possible opportunities (the green lightbulb).
	 */
	public function has_opportunities(): bool {
		return '' !== $this->monetize_with;
	}
}
