<?php
/**
 * Figures about a set of index rows.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\LinkIndex;

/**
 * How many hosts, links, tied links and products, and the links by type: for a rebuild's summary and the index's status.
 */
final class LinkIndexStats {

	/**
	 * The figures.
	 *
	 * @param int                $hosts    Host posts.
	 * @param int                $links    Rows.
	 * @param int                $tied     Rows tied to a product.
	 * @param int                $products Distinct products tied.
	 * @param array<string, int> $by_type  Rows by LinkType value.
	 * @param string             $latest   The newest row's time (UTC), '' when there is none.
	 */
	public function __construct(
		public readonly int $hosts = 0,
		public readonly int $links = 0,
		public readonly int $tied = 0,
		public readonly int $products = 0,
		public readonly array $by_type = array(),
		public readonly string $latest = '',
	) {}

	/**
	 * The figures of some rows.
	 *
	 * @param int                $hosts How many hosts the rows came from.
	 * @param list<LinkLocation> $rows  The rows.
	 */
	public static function of( int $hosts, array $rows ): self {
		$by_type  = array();
		$products = array();
		$tied     = 0;
		foreach ( $rows as $row ) {
			$by_type[ $row->link_type ] = ( $by_type[ $row->link_type ] ?? 0 ) + 1;
			if ( $row->product_id > 0 ) {
				++$tied;
				$products[ $row->product_id ] = true;
			}
		}

		return new self( $hosts, count( $rows ), $tied, count( $products ), $by_type );
	}
}
