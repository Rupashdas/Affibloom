<?php
/**
 * Where each product is used, as a count.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Locations;

use Affibloom\Domain\LinkIndex\LinkLocationRepository;
use Affibloom\Domain\LinkIndex\RebuildStateRepository;

/**
 * The Dashboard's Locations column (F12 L1-L3, D20, D21; Task 36A): each product's count from the link index, and
 * whether a 0 means "used nowhere" or "not known yet" (the first full index has not finished: the clock).
 */
final class LocationCounts {

	/**
	 * Builds the counts.
	 *
	 * @param LinkLocationRepository $rows  The link index.
	 * @param RebuildStateRepository $state Where its rebuild stands.
	 */
	public function __construct( private LinkLocationRepository $rows, private RebuildStateRepository $state ) {}

	/**
	 * Each product's count, 0 for a product used nowhere.
	 *
	 * @param list<int> $product_ids The products.
	 * @return array<int, int> By product ID, in the order asked.
	 */
	public function of( array $product_ids ): array {
		$found  = array() === $product_ids ? array() : $this->rows->counts_for( $product_ids );
		$counts = array();
		foreach ( $product_ids as $id ) {
			$counts[ $id ] = $found[ $id ] ?? 0;
		}

		return $counts;
	}

	/**
	 * Whether the counts are known: a full index has finished at least once (D21; Lasso's 24 hours after publishing
	 * is not copied, as Affibloom indexes a post when it is saved).
	 */
	public function known(): bool {
		return '' !== $this->state->get()->finished_at;
	}
}
