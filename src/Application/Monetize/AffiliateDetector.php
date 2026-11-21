<?php
/**
 * Recording a post's affiliate network links.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Monetize;

use Affibloom\Application\Products\ProductService;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\LinkIndex\LinkLocation;
use Affibloom\Domain\LinkIndex\LinkType;
use Affibloom\Domain\LinkIndex\ProductLocation;
use Affibloom\Domain\Monetize\DetectedLinkRepository;
use Affibloom\Domain\Monetize\NetworkExtractor;
use Affibloom\Domain\Monetize\NetworkRegistry;
use Closure;
use RuntimeException;

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
	 * @param ProductService|null    $products The add path, which makes their products (Task C2C).
	 */
	public function __construct( private NetworkRegistry $networks, private DetectedLinkRepository $detected, private ?ProductService $products = null ) {}

	/**
	 * Makes products for detected links without one (F35 R13, R14, D5; C2 AC1, AC2, AC8; Task C2C): through the add path,
	 * so a link that already is a product is reused (a trashed one is published again), a new one is named as a pasted
	 * link and takes the link defaults with Link Cloaking off, and its Primary URL is the link as written (D7). A link
	 * the add path refuses is skipped; the run stops when the setting is switched off.
	 *
	 * @param int     $limit    At most this many (Lasso's 100).
	 * @param Closure $still_on Whether Auto-Detect is still on: fn(): bool, asked before each link.
	 * @return int How many products were recorded.
	 */
	public function create_products( int $limit, Closure $still_on ): int {
		if ( null === $this->products ) {
			return 0;
		}
		$made = 0;
		foreach ( $this->detected->without_product( $limit ) as $id => $url ) {
			if ( ! $still_on() ) {
				break;
			}
			try {
				$added = $this->products->create_from_url( $url, true );
			} catch ( InvalidField | RuntimeException ) {
				continue;
			}
			$this->detected->set_product( $id, (int) $added->product->id );
			++$made;
		}

		return $made;
	}

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
