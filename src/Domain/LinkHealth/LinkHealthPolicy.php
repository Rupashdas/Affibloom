<?php
/**
 * How one check changes a product's link-health row.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\LinkHealth;

use DateTimeImmutable;

/**
 * How one check changes a product's row (F11 R13, D4, D6). Lasso's rule with Affibloom's second check in place of
 * Lasso's cloud: a 200 resolves the row and keeps its code and date; an HTTP failure counts once, and a second failure
 * in a row for the same destination, at least an hour after the first (D4), opens the issue dated by the first (one
 * sooner changes nothing, so the second check stays due on time, whichever event runs first); a certain result (blank
 * destination, product API) opens at once; an open issue that fails again stays open with its date; a check that never
 * went out changes nothing.
 */
final class LinkHealthPolicy {

	/** Seconds between a first failure and the check that can confirm it (D4). */
	public const CONFIRM_AFTER = 3600;

	/**
	 * The row after the check, or null when nothing changes.
	 *
	 * @param LinkIssue|null    $current    The stored row; null when the product has none.
	 * @param int               $product_id The product.
	 * @param string            $url        The destination checked.
	 * @param CheckResult       $result     What the check found.
	 * @param DateTimeImmutable $now        When it was checked.
	 */
	public static function apply( ?LinkIssue $current, int $product_id, string $url, CheckResult $result, DateTimeImmutable $now ): ?LinkIssue {
		if ( ! $result->was_checked() ) {
			return null;
		}
		if ( $result->is_ok() ) {
			return self::working( $current, $product_id, $url, $now );
		}
		if ( null !== $current && $current->is_open() ) {
			return self::cleared( $current, $url, $now )->with( array( 'issue_type' => $result->type ) );
		}
		if ( ! $result->needs_confirmation() ) {
			return self::opened( $current, $product_id, $url, $result->type, $now, $now );
		}
		if ( null !== $current && $current->failures > 0 && $current->checked_url === $url && null !== $current->first_failed_at ) {
			if ( $now->getTimestamp() - $current->first_failed_at->getTimestamp() < self::CONFIRM_AFTER ) {
				return null;
			}
			return self::opened( $current, $product_id, $url, $result->type, $current->first_failed_at, $now );
		}

		return self::suspect( $current, $product_id, $url, $result->type, $now );
	}

	/**
	 * The link works: a new checked row, or the row resolved with its code and date kept.
	 *
	 * @param LinkIssue|null    $current    The stored row.
	 * @param int               $product_id The product.
	 * @param string            $url        The destination.
	 * @param DateTimeImmutable $now        The check's time.
	 */
	private static function working( ?LinkIssue $current, int $product_id, string $url, DateTimeImmutable $now ): LinkIssue {
		if ( null === $current ) {
			return new LinkIssue( $product_id, IssueType::OK, $url, null, true, null, $now );
		}

		return self::cleared( $current, $url, $now )->with(
			array(
				'issue_type'  => IssueType::NONE === $current->issue_type ? IssueType::OK : $current->issue_type,
				'resolved'    => true,
				'resolved_at' => $current->is_open() ? $now : $current->resolved_at,
			)
		);
	}

	/**
	 * An open issue: opened now or confirmed, dated $since.
	 *
	 * @param LinkIssue|null    $current    The stored row.
	 * @param int               $product_id The product.
	 * @param string            $url        The destination.
	 * @param string            $type       The issue's code.
	 * @param DateTimeImmutable $since      When it began.
	 * @param DateTimeImmutable $now        The check's time.
	 */
	private static function opened( ?LinkIssue $current, int $product_id, string $url, string $type, DateTimeImmutable $since, DateTimeImmutable $now ): LinkIssue {
		$row = $current ?? new LinkIssue( $product_id, $type, $url, $since, false, null, $now );

		return self::cleared( $row, $url, $now )->with(
			array(
				'issue_type'     => $type,
				'detection_date' => $since,
				'resolved'       => false,
				'resolved_at'    => null,
			)
		);
	}

	/**
	 * A first failure, which waits for the second check; the row's result so far stays as it was.
	 *
	 * @param LinkIssue|null    $current    The stored row.
	 * @param int               $product_id The product.
	 * @param string            $url        The destination.
	 * @param string            $type       The failure's code.
	 * @param DateTimeImmutable $now        The check's time.
	 */
	private static function suspect( ?LinkIssue $current, int $product_id, string $url, string $type, DateTimeImmutable $now ): LinkIssue {
		$row = $current ?? new LinkIssue( $product_id, IssueType::NONE, $url, null, false, null, $now );

		return $row->with(
			array(
				'checked_url'     => $url,
				'last_checked_at' => $now,
				'failures'        => 1,
				'first_failed_at' => $now,
				'pending_type'    => $type,
			)
		);
	}

	/**
	 * The row checked now with no failure waiting.
	 *
	 * @param LinkIssue         $row The row.
	 * @param string            $url The destination.
	 * @param DateTimeImmutable $now The check's time.
	 */
	private static function cleared( LinkIssue $row, string $url, DateTimeImmutable $now ): LinkIssue {
		return $row->with(
			array(
				'checked_url'     => $url,
				'last_checked_at' => $now,
				'failures'        => 0,
				'first_failed_at' => null,
				'pending_type'    => null,
			)
		);
	}
}
