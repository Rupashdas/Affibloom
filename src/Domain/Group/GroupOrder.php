<?php
/**
 * The display order of a group's products (F23 D12).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Group;

/**
 * The display order of a group's products (F23 R12, D12). Pure rules over lists of product IDs.
 *
 * A group's products are its published members; the stored list only ranks them. An ID in the list that is no longer a
 * member is skipped, and a member that is not in it comes last (by ID), so a product deleted, trashed or brought back
 * needs no hook to keep the order right.
 */
final class GroupOrder {

	/**
	 * The members in display order.
	 *
	 * @param list<int> $members The published members, in any order.
	 * @param list<int> $stored  The stored order.
	 * @return list<int>
	 */
	public static function rank( array $members, array $stored ): array {
		$ranked = array();
		foreach ( $stored as $id ) {
			if ( in_array( $id, $members, true ) && ! in_array( $id, $ranked, true ) ) {
				$ranked[] = $id;
			}
		}
		$rest = array_values( array_diff( $members, $ranked ) );
		sort( $rest );

		return array_merge( $ranked, $rest );
	}

	/**
	 * A product added to the group goes last (R12).
	 *
	 * @param list<int> $order The group's order.
	 * @param int       $id    The product.
	 * @return list<int>
	 */
	public static function append( array $order, int $id ): array {
		return array_merge( self::without( $order, $id ), array( $id ) );
	}

	/**
	 * Products taken out of the group leave the order.
	 *
	 * @param list<int> $order The group's order.
	 * @param int       $id    The product.
	 * @return list<int>
	 */
	public static function without( array $order, int $id ): array {
		return array_values( array_filter( $order, static fn( int $item ): bool => $item !== $id ) );
	}

	/**
	 * One step up (-1) or down (+1), as the row's Move buttons do (D7). The first cannot go up, the last cannot go down.
	 *
	 * @param list<int> $order     The group's order.
	 * @param int       $id        The product to move.
	 * @param int       $direction -1 or 1.
	 * @return list<int>
	 */
	public static function move( array $order, int $id, int $direction ): array {
		$at = array_search( $id, $order, true );
		if ( false === $at ) {
			return $order;
		}
		$to = $at + ( $direction < 0 ? -1 : 1 );
		if ( $to < 0 || $to >= count( $order ) ) {
			return $order;
		}
		$swap         = $order[ $to ];
		$order[ $to ] = $id;
		$order[ $at ] = $swap;

		return $order;
	}
}
