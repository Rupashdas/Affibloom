<?php
/**
 * Finds the product a link in content belongs to.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\LinkIndex;

use Affibloom\Domain\Amazon\AmazonUrl;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Monetize\DetectedLinkRepository;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\PlainUrlKey;
use Affibloom\Domain\Product\ProductRepository;

/**
 * URL → product (F21 D8 without Lasso's own lookups, deviation 146): the product whose link is this link, then whose
 * link is this link without its query (Task 9C's plain key), then the Amazon product with this ASIN on this store.
 * Published products only; with two candidates, the first by ID (edge case 6). The cloaked permalink is CL1's arm; the
 * product recorded for a detected affiliate link comes last (F35 R15; Task C2C).
 * Answers are kept for the run, since one link often repeats across posts.
 */
final class ProductResolver {

	/**
	 * Answers so far, by link.
	 *
	 * @var array<string, int>
	 */
	private array $known = array();

	/**
	 * Builds the resolver.
	 *
	 * @param ProductRepository           $products The library.
	 * @param DetectedLinkRepository|null $detected The detected affiliate links, looked up last (F35 R15; Task C2C).
	 */
	public function __construct( private ProductRepository $products, private ?DetectedLinkRepository $detected = null ) {}

	/**
	 * The product's ID, or 0 when the link is no product's.
	 *
	 * @param string $url The link as written.
	 */
	public function resolve( string $url ): int {
		if ( ! isset( $this->known[ $url ] ) ) {
			$this->known[ $url ] = $this->look_up( $url );
		}

		return $this->known[ $url ];
	}

	/**
	 * The lookups, in order.
	 *
	 * @param string $url The link as written.
	 */
	private function look_up( string $url ): int {
		try {
			$link = AffiliateUrl::from_string( $url );
		} catch ( InvalidField ) {
			return 0;
		}

		$product = $this->products->find_by_url_key( $link->key() );
		$plain   = PlainUrlKey::of( $link );
		if ( null === $product && '' !== $plain ) {
			$product = $this->products->find_by_plain_key( $plain );
		}
		$amazon = AmazonUrl::from( $link );
		if ( null === $product && null !== $amazon && '' !== $amazon->asin() ) {
			$product = $this->products->find_amazon( $amazon->asin(), $amazon->country() );
		}
		// The product made for a detected affiliate link (F35 R15, D6): the link prints it even when the product's own
		// link has changed since; only while it is published.
		$detected = null === $product && null !== $this->detected ? $this->detected->product_for( $url ) : 0;
		if ( $detected > 0 ) {
			$product = $this->products->get( $detected );
		}

		return (int) $product?->id;
	}
}
