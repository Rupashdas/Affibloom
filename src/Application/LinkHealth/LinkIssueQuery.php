<?php
/**
 * Link health as the screens read it.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\LinkHealth;

use Affibloom\Domain\LinkHealth\AlertListing;
use Affibloom\Domain\LinkHealth\AlertState;
use Affibloom\Domain\LinkHealth\AlertWindow;
use Affibloom\Domain\LinkHealth\LinkIssueRepository;
use Affibloom\Domain\Settings\SettingsRepository;
use Closure;
use DateTimeImmutable;

/**
 * Link health as the screens read it (F11 analysis, "Entry points"): each Dashboard row's Alert cell (Task 31A) and
 * the list's Alert sort and tab, with "Days before out of stock alerts" applied everywhere (D14).
 */
final class LinkIssueQuery {

	/** How long a new, unchecked product shows the clock (R21: published within 24 hours). */
	private const PENDING_FOR = '-24 hours';

	/**
	 * Builds the query.
	 *
	 * @param LinkIssueRepository          $issues   The rows.
	 * @param SettingsRepository           $settings The grace days.
	 * @param Closure(): DateTimeImmutable $now      The time now.
	 */
	public function __construct( private LinkIssueRepository $issues, private SettingsRepository $settings, private Closure $now ) {}

	/**
	 * Each product's Alert cell.
	 *
	 * @param list<int> $product_ids The products on screen.
	 * @return array<int, AlertState> By product ID.
	 */
	public function for_products( array $product_ids ): array {
		if ( array() === $product_ids ) {
			return array();
		}
		$now    = ( $this->now )();
		$rows   = $this->issues->rows( $product_ids );
		$recent = array_flip( $this->issues->published_after( $product_ids, $now->modify( self::PENDING_FOR ) ) );
		$window = $this->window();
		$states = array();
		foreach ( $product_ids as $id ) {
			$states[ $id ] = AlertState::of( $rows[ $id ] ?? null, isset( $recent[ $id ] ), $window, $now );
		}

		return $states;
	}

	/**
	 * The list's Alert sort and tab as asked for.
	 *
	 * @param string $order AlertListing::ASC, AlertListing::DESC or ''.
	 * @param string $tab   AlertListing::BROKEN, AlertListing::OUT_OF_STOCK or ''.
	 */
	public function listing( string $order, string $tab ): AlertListing {
		return new AlertListing( $order, $tab, $this->window()->cutoff( ( $this->now )() ) );
	}

	/**
	 * "Days before out of stock alerts".
	 */
	private function window(): AlertWindow {
		return new AlertWindow( $this->settings->get()->int( 'days_before_out_of_stock_alerts' ) );
	}
}
