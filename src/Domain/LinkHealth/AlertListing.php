<?php
/**
 * How the Dashboard list uses link health: the Alert sort and the alert tab.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\LinkHealth;

use DateTimeImmutable;

/**
 * The Dashboard list's link-health arguments (F11 R20, R25, R26; Tasks 31A, 31B): the Alert column's sort by the
 * shown issue's detection date (ascending, descending, or none, products without a shown issue last), and the tab
 * that lists only broken, only out-of-stock products, or only those with Link Opportunities (F22, Task O8). Out-of-stock issues count only once the grace days have
 * passed ($cutoff, from AlertWindow).
 */
final class AlertListing {

	public const ASC  = 'asc';
	public const DESC = 'desc';

	public const BROKEN       = 'broken-links';
	public const OUT_OF_STOCK = 'out-of-stock';
	/** The products with Link Opportunities (F22 R50-R52, D13; Task O8). */
	public const OPPORTUNITIES = 'opportunities';

	/** Every tab. */
	public const TABS = array( self::BROKEN, self::OUT_OF_STOCK, self::OPPORTUNITIES );

	/**
	 * The sort: self::ASC, self::DESC or empty (the list's own order).
	 *
	 * @var string
	 */
	public readonly string $order;

	/**
	 * The tab: self::BROKEN, self::OUT_OF_STOCK, self::OPPORTUNITIES or empty (every product).
	 *
	 * @var string
	 */
	public readonly string $tab;

	/**
	 * Builds the arguments; values outside the lists mean none.
	 *
	 * @param string            $order  The sort asked for.
	 * @param string            $tab    The tab asked for.
	 * @param DateTimeImmutable $cutoff The latest detection date of an out-of-stock issue that shows.
	 */
	public function __construct( string $order, string $tab, public readonly DateTimeImmutable $cutoff ) {
		$this->order = in_array( $order, array( self::ASC, self::DESC ), true ) ? $order : '';
		$this->tab   = in_array( $tab, self::TABS, true ) ? $tab : '';
	}

	/**
	 * Whether it changes the list at all.
	 */
	public function is_active(): bool {
		return '' !== $this->order || '' !== $this->tab;
	}
}
