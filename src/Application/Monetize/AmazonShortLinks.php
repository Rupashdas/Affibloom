<?php
/**
 * Where an Amazon short link leads.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Monetize;

use Affibloom\Application\Products\LinkResolver;
use Affibloom\Domain\Amazon\AmazonUrl;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Monetize\ShortLinkRepository;
use Affibloom\Domain\Product\AffiliateUrl;

/**
 * Amazon short links resolved once (F35 R28, D14; F35 AC3; Task AM2): the map answers first; else the short link's own
 * host is asked by the add path's rule (F2 §5: the request stops before the Amazon store it leads to, which is never
 * fetched) and the answer is kept. A link that does not lead to an Amazon product page counts as a failed try; after
 * three it is left as it is.
 */
final class AmazonShortLinks {

	/**
	 * Builds the service.
	 *
	 * @param ShortLinkRepository $map   The map.
	 * @param LinkResolver        $links The add path's resolver.
	 */
	public function __construct( private ShortLinkRepository $map, private LinkResolver $links ) {}

	/**
	 * The Amazon product page a short link leads to, or null when it is not (yet) known.
	 *
	 * @param string $short_url The short link.
	 */
	public function resolve( string $short_url ): ?string {
		$row = $this->map->find( $short_url );
		if ( null !== $row && '' !== $row['final'] ) {
			return $row['final'];
		}
		$tries = null === $row ? 0 : $row['tries'];
		if ( $tries >= ShortLinkRepository::TRIES ) {
			return null;
		}
		$final = $this->destination( $short_url );
		$this->map->save( $short_url, $final ?? '', 0, null === $final ? $tries + 1 : $tries );

		return $final;
	}

	/**
	 * Records the product a short link's destination became.
	 *
	 * @param string $short_url The short link.
	 * @param int    $product   The product.
	 */
	public function made( string $short_url, int $product ): void {
		$row = $this->map->find( $short_url );
		if ( null !== $row ) {
			$this->map->save( $short_url, $row['final'], $product, $row['tries'] );
		}
	}

	/**
	 * One request: the Amazon product page the link leads to, or null.
	 *
	 * @param string $short_url The short link.
	 */
	private function destination( string $short_url ): ?string {
		try {
			$final = AffiliateUrl::from_string( $this->links->resolve( AffiliateUrl::from_string( $short_url ) )->url );
		} catch ( InvalidField ) {
			return null;
		}
		$amazon = AmazonUrl::from( $final );

		return null !== $amazon && ! AmazonUrl::is_short( $final ) && '' !== $amazon->asin() ? $final->value() : null;
	}
}
