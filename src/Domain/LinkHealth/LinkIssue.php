<?php
/**
 * A product's link-health row.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\LinkHealth;

use DateTimeImmutable;

/**
 * A product's link-health row (F11 D1, D4; Lasso's lasso_url_issues): one per product, for its primary destination.
 * An open issue is a row not resolved with a code; a row with no code (IssueType::NONE) only waits for the second
 * check of its first failure and is not an issue.
 */
final class LinkIssue {

	/**
	 * Builds a row.
	 *
	 * @param int                    $product_id      The product.
	 * @param string                 $issue_type      Its code (IssueType): the issue's, `200` when it never failed.
	 * @param string                 $checked_url     The destination last checked.
	 * @param DateTimeImmutable|null $detection_date  When the issue began (the first failure); null for none.
	 * @param bool                   $resolved        The link works again (or never failed).
	 * @param DateTimeImmutable|null $resolved_at     When it was found working after an issue.
	 * @param DateTimeImmutable      $last_checked_at The last check.
	 * @param int                    $failures        Failures in a row not yet confirmed (0 or 1, D4).
	 * @param DateTimeImmutable|null $first_failed_at When the unconfirmed failure happened.
	 * @param string|null            $pending_type    Its code.
	 */
	public function __construct(
		public readonly int $product_id,
		public readonly string $issue_type,
		public readonly string $checked_url,
		public readonly ?DateTimeImmutable $detection_date,
		public readonly bool $resolved,
		public readonly ?DateTimeImmutable $resolved_at,
		public readonly DateTimeImmutable $last_checked_at,
		public readonly int $failures = 0,
		public readonly ?DateTimeImmutable $first_failed_at = null,
		public readonly ?string $pending_type = null,
	) {}

	/**
	 * Whether the product has an issue now (broken or out of stock).
	 */
	public function is_open(): bool {
		return ! $this->resolved && IssueType::NONE !== $this->issue_type;
	}

	/**
	 * A copy with some properties changed.
	 *
	 * @param array<string, mixed> $changes Property name => new value.
	 */
	public function with( array $changes ): self {
		return new self( ...array_merge( get_object_vars( $this ), $changes ) );
	}
}
