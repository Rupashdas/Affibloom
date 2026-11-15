<?php
/**
 * Whether Amazon can sell an item now, from its listing's availability.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Amazon;

/**
 * Whether Amazon can sell an item now (C1 R12; Lasso's extract_quantity()), from the first listing's availability type
 * and message. Lasso turns it into a quantity of 0 or 200; only "in stock or not" is read anywhere (the cache's
 * out_of_stock, the link-health issue), so that is what this keeps.
 */
final class Availability {

	/** Types that mean the item cannot be bought now. */
	private const OUT = array( 'OUT_OF_STOCK', 'UNAVAILABLE', 'UNKNOWN' );

	/** Types that mean it can (or can be ordered). */
	private const IN = array( 'IN_STOCK', 'IN_STOCK_SCARCE', 'AVAILABLE_DATE', 'LEADTIME', 'PREORDER' );

	/**
	 * Use from_listing() or none().
	 *
	 * @param bool   $in_stock Whether it can be bought.
	 * @param string $type     Amazon's availability type, upper case, or ''.
	 * @param string $message  Amazon's availability message, or ''.
	 */
	private function __construct( public readonly bool $in_stock, public readonly string $type, public readonly string $message ) {}

	/**
	 * No listing at all: out of stock, as Lasso.
	 */
	public static function none(): self {
		return new self( false, '', '' );
	}

	/**
	 * From a listing's availability: a known type decides; otherwise a message containing "out" or "unavailable" means
	 * out of stock; anything else in stock (Lasso's rule).
	 *
	 * @param string $type    Amazon's availability type.
	 * @param string $message Amazon's availability message.
	 */
	public static function from_listing( string $type, string $message ): self {
		$type  = strtoupper( $type );
		$lower = strtolower( $message );

		if ( in_array( $type, self::OUT, true ) ) {
			$in_stock = false;
		} elseif ( in_array( $type, self::IN, true ) ) {
			$in_stock = true;
		} else {
			$in_stock = ! str_contains( $lower, 'out' ) && ! str_contains( $lower, 'unavailable' );
		}

		return new self( $in_stock, $type, $message );
	}
}
