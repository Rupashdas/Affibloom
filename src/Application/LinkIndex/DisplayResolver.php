<?php
/**
 * Finds the product or group a display shows.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\LinkIndex;

use Affibloom\Domain\Group\GroupRepository;
use Affibloom\Domain\LinkIndex\DisplayCandidate;
use Affibloom\Domain\Product\ProductRepository;

/**
 * A display's product (F31 A1: its slug when that finds a published product, else its ID; else its `amazon_url` or
 * `geni_url` as a link) and its group as `display_ref` "group:<term id>" (F21 D2: the members are read when counted,
 * not stored). Published products only, as the resolver of links.
 */
final class DisplayResolver {

	/**
	 * Builds the resolver.
	 *
	 * @param ProductRepository $products The library.
	 * @param GroupRepository   $groups   The groups.
	 * @param ProductResolver   $links    Ties a link to its product.
	 */
	public function __construct( private ProductRepository $products, private GroupRepository $groups, private ProductResolver $links ) {}

	/**
	 * The display's product ID (0 when none).
	 *
	 * @param DisplayCandidate $display The display.
	 */
	public function product( DisplayCandidate $display ): int {
		if ( '' !== $display->product_slug ) {
			$by_slug = $this->products->id_by_slug( $display->product_slug );
			if ( null !== $by_slug && null !== $this->products->get( $by_slug ) ) {
				return $by_slug;
			}
		}
		if ( $display->product_id > 0 && null !== $this->products->get( $display->product_id ) ) {
			return $display->product_id;
		}

		return '' !== $display->product_link ? $this->links->resolve( $display->product_link ) : 0;
	}

	/**
	 * "group:<term id>" for a display of an existing group, else null.
	 *
	 * @param DisplayCandidate $display The display.
	 */
	public function reference( DisplayCandidate $display ): ?string {
		$group = $display->group_id;
		if ( $group <= 0 && '' !== $display->group_slug ) {
			$group = (int) $this->groups->find_id_by_slug( $display->group_slug );
		}

		return $group > 0 && null !== $this->groups->find( $group ) ? 'group:' . $group : null;
	}
}
