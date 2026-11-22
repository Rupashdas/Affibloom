<?php
/**
 * What the extension API may do with link issues and their dismissals.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Extension;

use Affibloom\Application\LinkHealth\AlertDismissals;
use Affibloom\Domain\LinkHealth\LinkIssue;
use Affibloom\Domain\LinkHealth\LinkIssueRepository;
use Affibloom\Domain\Product\ProductRepository;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * What the extension API may do with a product's link-health row and its closed issue bars (Task 26K): read them, and
 * store another plugin's row and dismissals, so the Alert column shows what that plugin showed before Affibloom's own
 * first check. Times are UTC, "Y-m-d H:i:s"; '' for none.
 */
final class IssueAccess {

	private const TIME = 'Y-m-d H:i:s';

	/**
	 * Builds the access.
	 *
	 * @param LinkIssueRepository $issues     The link-health rows.
	 * @param AlertDismissals     $dismissals The closed issue bars.
	 * @param ProductRepository   $products   The products.
	 */
	public function __construct( private LinkIssueRepository $issues, private AlertDismissals $dismissals, private ProductRepository $products ) {}

	/**
	 * A product's row, or null when it has none.
	 *
	 * @param int $product The product.
	 * @return array{issue_type: string, checked_url: string, detection_date: string, resolved: bool, resolved_at: string, last_checked_at: string}|null
	 */
	public function get( int $product ): ?array {
		$issue = $this->issues->get( $product );

		return null === $issue ? null : array(
			'issue_type'      => $issue->issue_type,
			'checked_url'     => $issue->checked_url,
			'detection_date'  => self::text( $issue->detection_date ),
			'resolved'        => $issue->resolved,
			'resolved_at'     => self::text( $issue->resolved_at ),
			'last_checked_at' => self::text( $issue->last_checked_at ),
		);
	}

	/**
	 * Stores a published product's row as given, with no failure waiting for confirmation.
	 *
	 * @param int                  $product The product.
	 * @param array<string, mixed> $row     The keys get() gives.
	 * @throws InvalidArgumentException When there is no such product, or the code or the last check is not one.
	 */
	public function import( int $product, array $row ): void {
		if ( null === $this->products->get( $product ) ) {
			throw new InvalidArgumentException( esc_html( "No product $product." ) );
		}
		$type    = (string) ( $row['issue_type'] ?? '' );
		$checked = self::time( (string) ( $row['last_checked_at'] ?? '' ) );
		if ( 1 !== preg_match( '/^\d{3}$/', $type ) || null === $checked ) {
			throw new InvalidArgumentException( 'issue: invalid' );
		}
		$this->issues->save(
			new LinkIssue(
				$product,
				$type,
				(string) ( $row['checked_url'] ?? '' ),
				self::time( (string) ( $row['detection_date'] ?? '' ) ),
				(bool) ( $row['resolved'] ?? false ),
				self::time( (string) ( $row['resolved_at'] ?? '' ) ),
				$checked
			)
		);
	}

	/**
	 * Until when a product's issue bar stays closed, a Unix time; 0 when it is open.
	 *
	 * @param int    $product The product.
	 * @param string $bar     `broken` or `out-of-stock`.
	 */
	public function dismissed_until( int $product, string $bar ): int {
		return $this->dismissals->until( $product, self::bar( $bar ) );
	}

	/**
	 * Closes a product's issue bar until a time.
	 *
	 * @param int    $product The product.
	 * @param string $bar     `broken` or `out-of-stock`.
	 * @param int    $until   A Unix time.
	 */
	public function dismiss( int $product, string $bar, int $until ): void {
		$this->dismissals->close( $product, self::bar( $bar ), $until );
	}

	/**
	 * A bar's name, checked.
	 *
	 * @param string $bar The name given.
	 * @throws InvalidArgumentException When it is not a bar.
	 */
	private static function bar( string $bar ): string {
		if ( ! in_array( $bar, array( AlertDismissals::BROKEN, AlertDismissals::OUT_OF_STOCK ), true ) ) {
			throw new InvalidArgumentException( 'bar: invalid' );
		}

		return $bar;
	}

	/**
	 * A UTC time from its text, or null.
	 *
	 * @param string $text "Y-m-d H:i:s".
	 */
	private static function time( string $text ): ?DateTimeImmutable {
		$time = '' === $text || str_starts_with( $text, '0000' ) ? false : DateTimeImmutable::createFromFormat( self::TIME, $text, new DateTimeZone( 'UTC' ) );

		return false === $time ? null : $time;
	}

	/**
	 * A time as text, or ''.
	 *
	 * @param DateTimeImmutable|null $time The time.
	 */
	private static function text( ?DateTimeImmutable $time ): string {
		return null === $time ? '' : $time->setTimezone( new DateTimeZone( 'UTC' ) )->format( self::TIME );
	}
}
