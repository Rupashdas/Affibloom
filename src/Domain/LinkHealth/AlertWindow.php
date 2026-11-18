<?php
/**
 * "Days before out of stock alerts": when an out-of-stock issue starts to show.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\LinkHealth;

use DateTimeImmutable;

/**
 * Settings → Amazon's "Days before out of stock alerts" (F11 R27, D14; AC11): an out-of-stock issue younger than that
 * shows nowhere (the Alert cell, the counts, the tab, the product page, the export). Broken links always show. Lasso
 * stores 0-30; a value outside is clamped.
 */
final class AlertWindow {

	/**
	 * The grace days, 0-30.
	 *
	 * @var int
	 */
	public readonly int $days;

	/**
	 * Builds the window.
	 *
	 * @param int $days The setting's value.
	 */
	public function __construct( int $days ) {
		$this->days = max( 0, min( 30, $days ) );
	}

	/**
	 * Whether a row's issue shows now: an open broken issue always, an open out-of-stock one once its grace has passed.
	 *
	 * @param LinkIssue         $row The row.
	 * @param DateTimeImmutable $now The time now.
	 */
	public function shows( LinkIssue $row, DateTimeImmutable $now ): bool {
		if ( ! $row->is_open() ) {
			return false;
		}
		if ( IssueType::OUT_OF_STOCK !== $row->issue_type ) {
			return true;
		}

		return null !== $row->detection_date && $row->detection_date <= $this->cutoff( $now );
	}

	/**
	 * The latest detection date of an out-of-stock issue that shows now.
	 *
	 * @param DateTimeImmutable $now The time now.
	 */
	public function cutoff( DateTimeImmutable $now ): DateTimeImmutable {
		return $now->modify( '-' . $this->days . ' days' );
	}
}
