<?php
/**
 * Lasso's rule for a box's price timestamp.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Box;

use DateTimeImmutable;
use DateTimeZone;

/**
 * The "as of" line (F5 R27, R29, D6; Lasso's single.php 234-246 and get_lasso_url 352): never on a basic link (a
 * product that is not Amazon's); in advanced rendering by the Timestamp element, in classic rendering with a printed
 * price (R27).
 * Its value is the Amazon data's last update in UTC, as Lasso prints it with gmdate (`m/d/Y h:i a T`, "GMT"); a product
 * Amazon never answered for has none, and Lasso then prints the tooltip icon alone.
 */
final class TimestampRule {

	/**
	 * Whether the advanced box shows the timestamp.
	 *
	 * @param bool $amazon  The product is an Amazon product.
	 * @param bool $element The Timestamp element shows.
	 */
	public static function shows( bool $amazon, bool $element ): bool {
		return $amazon && $element;
	}

	/**
	 * Whether the classic box shows the timestamp (R27): an Amazon product whose price is printed.
	 *
	 * @param bool $amazon        The product is an Amazon product.
	 * @param bool $price_printed The box prints a price (PriceRule::prints).
	 */
	public static function shows_classic( bool $amazon, bool $price_printed ): bool {
		return $amazon && $price_printed;
	}

	/**
	 * The last update as Lasso prints it; '' for none or an unreadable time.
	 *
	 * @param string $last_updated The Amazon data's last update, "Y-m-d H:i:s" in UTC; '' when never fetched.
	 */
	public static function text( string $last_updated ): string {
		$time = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', trim( $last_updated ), new DateTimeZone( 'UTC' ) );
		if ( false === $time ) {
			return '';
		}

		return $time->setTimezone( new DateTimeZone( 'GMT' ) )->format( 'm/d/Y h:i a T' );
	}
}
