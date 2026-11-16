<?php
/**
 * An Amazon price written as Lasso writes it.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Amazon;

/**
 * Lasso's price formatting (C1 R50; Lasso_Amazon_Api::build_price_with_currency_iso, Helper::get_currency_symbol_from_iso_code):
 * the currency's symbol, SEK as `1 234,50 kr`, the comma-decimal currencies as `1.234,50 €`, every other one as
 * `$1,234.50` (₹ in front). Prices are numbers here (the stored `price_amount`), so Lasso's text parsing is not needed.
 */
final class PriceFormat {

	/**
	 * Lasso's symbols by ISO code; any other code is `$`.
	 *
	 * @var array<string, string>
	 */
	private const SYMBOLS = array(
		'USD' => '$',
		'AUD' => '$',
		'CAD' => '$',
		'EUR' => '€',
		'MXN' => '$',
		'CNY' => '¥',
		'JPY' => '¥',
		'INR' => '₹',
		'SEK' => 'kr',
		'BRL' => 'R$',
		'TRY' => 'TL',
		'GBP' => '£',
		'PLN' => 'zł',
		'EGP' => 'E£',
		'SGD' => 'S$',
		'AED' => 'AED',
	);

	/**
	 * The symbols written after the amount with a comma for decimals.
	 *
	 * @var list<string>
	 */
	private const COMMA_DECIMALS = array( '€', 'R$', 'TL', 'zł' );

	/**
	 * The price as Lasso prints it.
	 *
	 * @param float  $amount   The price.
	 * @param string $currency Its ISO code.
	 */
	public static function format( float $amount, string $currency ): string {
		$symbol = self::symbol( $currency );
		if ( 'kr' === $symbol ) {
			return number_format( $amount, 2, ',', ' ' ) . ' kr';
		}
		if ( self::comma_decimals( $symbol ) ) {
			return number_format( $amount, 2, ',', '.' ) . ' ' . $symbol;
		}

		return $symbol . number_format( $amount, 2, '.', ',' );
	}

	/**
	 * The currency's symbol.
	 *
	 * @param string $currency An ISO code.
	 */
	public static function symbol( string $currency ): string {
		return self::SYMBOLS[ strtoupper( $currency ) ] ?? '$';
	}

	/**
	 * Whether the symbol is written after an amount with comma decimals (Lasso's regex `[€]|R\$|TL|kr|zł`).
	 *
	 * @param string $symbol A symbol from symbol().
	 */
	public static function comma_decimals( string $symbol ): bool {
		return 'kr' === $symbol || in_array( $symbol, self::COMMA_DECIMALS, true );
	}
}
