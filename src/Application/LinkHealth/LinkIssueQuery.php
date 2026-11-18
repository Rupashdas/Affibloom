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
use Affibloom\Domain\Product\LinkKind;
use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Domain\Product\SearchTerm;
use Affibloom\Domain\Settings\SettingsRepository;
use Closure;
use DateTimeImmutable;

/**
 * Link health as the screens read it (F11 analysis, "Entry points"): each Dashboard row's Alert cell (Task 31A) and
 * the list's Alert sort and tab, and the tabs' counts (Task 31B), with "Days before out of stock alerts" applied
 * everywhere (D14).
 */
final class LinkIssueQuery {

	/** How long a new, unchecked product shows the clock (R21: published within 24 hours). */
	private const PENDING_FOR = '-24 hours';

	/** How long after activation the counts may still grow while the first checks run (D16). */
	private const WARM_UP = 86400;

	/**
	 * Builds the query.
	 *
	 * @param LinkIssueRepository          $issues   The rows.
	 * @param SettingsRepository           $settings The grace days.
	 * @param Closure(): DateTimeImmutable $now      The time now.
	 * @param ProductRepository            $products The list the counts follow.
	 * @param AmazonProductCheck           $amazon   Whether Amazon products are checked (D7).
	 * @param Closure(): int               $activated When the plugin was activated (Unix time; 0 unknown).
	 */
	public function __construct(
		private LinkIssueRepository $issues,
		private SettingsRepository $settings,
		private Closure $now,
		private ProductRepository $products,
		private AmazonProductCheck $amazon,
		private Closure $activated,
	) {}

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
	 * The tabs' counts (F11 R24, D15, D16, D7): broken and out-of-stock products in the list as searched and filtered
	 * (the tab itself aside), whether the first day's checks may still change them, and whether Amazon products are
	 * checked at all.
	 *
	 * @param SearchTerm $term      The search.
	 * @param LinkKind   $link_kind The link-type filter.
	 * @param int        $group     The Group filter.
	 * @param int        $post_id   One product only; 0 for all.
	 * @return array{broken: int, out_of_stock: int, warm_up: bool, amazon_api: bool}
	 */
	public function counts( SearchTerm $term, LinkKind $link_kind, int $group = 0, int $post_id = 0 ): array {
		$count     = fn( string $tab ): int => $this->products->page( $term, $link_kind, 1, 1, $group, $post_id, $this->listing( '', $tab ) )->total;
		$activated = ( $this->activated )();

		return array(
			'broken'       => $count( AlertListing::BROKEN ),
			'out_of_stock' => $count( AlertListing::OUT_OF_STOCK ),
			'warm_up'      => $activated > 0 && ( $this->now )()->getTimestamp() - $activated < self::WARM_UP,
			'amazon_api'   => $this->amazon->is_configured(),
		);
	}

	/**
	 * "Days before out of stock alerts".
	 */
	private function window(): AlertWindow {
		return new AlertWindow( $this->settings->get()->int( 'days_before_out_of_stock_alerts' ) );
	}
}
