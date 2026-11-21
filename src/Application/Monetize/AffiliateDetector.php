<?php
/**
 * Recording a post's affiliate network links.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Monetize;

use Affibloom\Domain\LinkIndex\LinkLocation;
use Affibloom\Domain\LinkIndex\LinkType;
use Affibloom\Domain\LinkIndex\ProductLocation;
use Affibloom\Domain\Monetize\DetectedLinkRepository;
use Affibloom\Domain\Monetize\NetworkExtractor;
use Affibloom\Domain\Monetize\NetworkRegistry;

/**
 * Lasso's detection during its scan (F35 R10, R12, D3; Task C2B), on the rows the link index has just written for a
 * post: each text or image link of one of the 32 detected networks (Amazon is the Amazon import's, the four of R8 are
 * never detected) is recorded once per URL, tied to its product when the link already is one. Nothing in the post
 * changes.
 */
final class AffiliateDetector {

	/**
	 * Builds the detector.
	 *
	 * @param NetworkRegistry        $networks Lasso's networks.
	 * @param DetectedLinkRepository $detected Where the links are kept.
	 */
	public function __construct( private NetworkRegistry $networks, private DetectedLinkRepository $detected ) {}

	/**
	 * Records a post's network links.
	 *
	 * @param list<LinkLocation> $rows The post's index rows.
	 * @return int How many of them have no product yet (C2C makes them).
	 */
	public function detect( array $rows ): int {
		$seen    = array();
		$pending = 0;
		foreach ( $rows as $row ) {
			if ( isset( $seen[ $row->url ] ) || ! in_array( $row->link_type, array( LinkType::External->value, LinkType::Product->value ), true )
				|| ( new ProductLocation( 0, $row->display_type, $row->url ) )->is_display() ) {
				continue;
			}
			$seen[ $row->url ] = true;
			$network           = $this->networks->match( $row->url, false );
			if ( null === $network || ! $network->is_detected() ) {
				continue;
			}
			$product = $row->assigned_product_id > 0 ? $row->assigned_product_id : $row->product_id;
			$this->detected->record( $row->url, NetworkExtractor::read( $network, $row->url ), $product );
			$pending += 0 === $product ? 1 : 0;
		}

		return $pending;
	}
}
