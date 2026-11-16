<?php
/**
 * Which stored Amazon price a box may show.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Amazon;

use DateTimeImmutable;
use DateTimeZone;

/**
 * An Amazon product's price and discount from its stored data (C1 R48, R51, N3, decision E6): only a row Amazon's API
 * gave (`creators`) within the last 24 hours shows a price (Amazon's rules allow prices only from the API); a migrated
 * or older row shows none. Lasso replaces the product's `price` meta with the cache's price; so does this.
 */
final class ShownPrice {

	/**
	 * How old a row may be and still show its price: 24 hours, in seconds.
	 */
	private const MAX_AGE = 86400;

	/**
	 * Use of().
	 *
	 * @param string $price    The price as printed; '' none.
	 * @param string $discount The struck basis as printed; '' none.
	 */
	private function __construct( public readonly string $price, public readonly string $discount ) {}

	/**
	 * The price and discount of a stored row at a moment.
	 *
	 * @param AmazonProduct|null $row The product's stored Amazon data.
	 * @param DateTimeImmutable  $now The moment (UTC).
	 */
	public static function of( ?AmazonProduct $row, DateTimeImmutable $now ): self {
		if ( null === $row || 'creators' !== $row->source ) {
			return new self( '', '' );
		}
		$updated = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $row->last_updated, new DateTimeZone( 'UTC' ) );
		if ( false === $updated || $now->getTimestamp() - $updated->getTimestamp() > self::MAX_AGE ) {
			return new self( '', '' );
		}

		$price = null === $row->price_amount ? $row->price : PriceFormat::format( $row->price_amount, $row->currency );

		return new self( $price, DiscountPrice::basis( $row->price_amount, $row->basis_amount, $row->currency ) );
	}
}
