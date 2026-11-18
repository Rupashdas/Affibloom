<?php
/**
 * What a product's Alert cell shows.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\LinkHealth;

use DateTimeImmutable;

/**
 * A product's Alert cell (F11 R21, R22, D13, D14; Lasso's url-row.php 112-158): broken (red unlink, "Broken since"),
 * out of stock (orange box, "Out of stock since", after the grace days), checked (green tick: checked and no issue
 * shows), pending (grey clock: published in the last 24 hours and not checked yet), or nothing. The days line counts
 * the day of detection as day 1 (Lasso: days + 1).
 */
final class AlertState {

	public const BROKEN       = 'broken';
	public const OUT_OF_STOCK = 'out-of-stock';
	public const CHECKED      = 'checked';
	public const PENDING      = 'pending';
	public const NONE         = 'none';

	/**
	 * Use of().
	 *
	 * @param string                 $kind     One of the constants.
	 * @param DateTimeImmutable|null $detected When the shown issue began; null for none.
	 * @param int                    $days     Its days line; 0 for none.
	 */
	private function __construct( public readonly string $kind, public readonly ?DateTimeImmutable $detected = null, public readonly int $days = 0 ) {}

	/**
	 * The cell for a product.
	 *
	 * @param LinkIssue|null    $row    Its link-health row; null when it has none.
	 * @param bool              $recent Published in the last 24 hours.
	 * @param AlertWindow       $window The grace days.
	 * @param DateTimeImmutable $now    The time now.
	 */
	public static function of( ?LinkIssue $row, bool $recent, AlertWindow $window, DateTimeImmutable $now ): self {
		if ( null !== $row && $window->shows( $row, $now ) && null !== $row->detection_date ) {
			$kind = IssueType::OUT_OF_STOCK === $row->issue_type ? self::OUT_OF_STOCK : self::BROKEN;
			$days = (int) $row->detection_date->setTime( 0, 0 )->diff( $now->setTime( 0, 0 ) )->days + 1;

			return new self( $kind, $row->detection_date, $days );
		}
		$checked = null !== $row && IssueType::NONE !== $row->issue_type;
		if ( $checked && ! $row->is_open() ) {
			return new self( self::CHECKED );
		}
		if ( ! $checked && $recent ) {
			return new self( self::PENDING );
		}

		return new self( self::NONE );
	}

	/**
	 * The issue's date as Lasso writes it ("Nov 16, 2026"); '' when there is none.
	 */
	public function since(): string {
		return null === $this->detected ? '' : $this->detected->format( 'M j, Y' );
	}
}
