<?php
/**
 * The listing an Amazon item is read from: its prices as numbers, seller, condition and deal.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Amazon;

/**
 * The listing an item is read from (C1Q2): Amazon's buy-box winner, else the first listing. Prices are numbers here, so
 * discounts and comparisons can be computed (the display strings stay in CatalogItem). A field Amazon leaves out is null
 * or ''; an out-of-stock listing has no price, seller or condition.
 */
final class OfferDetails {

	/**
	 * Builds the listing's details.
	 *
	 * @param float|null $price_amount   The price (price.money.amount), or null.
	 * @param float|null $basis_amount   The list or was price the saving is from (savingBasis.money.amount), or null.
	 * @param string     $basis_type     Its kind: LIST_PRICE, WAS_PRICE, LOWEST_PRICE, LOWEST_PRICE_STRIKETHROUGH, or ''.
	 * @param float|null $savings_amount The saving (savings.money.amount), or null.
	 * @param string     $seller         The seller (merchantInfo.name), or ''.
	 * @param string     $condition      New, Used, Refurbished or Unknown, or ''.
	 * @param bool       $buy_box        Whether this listing is the buy-box winner.
	 * @param string     $deal_badge     The deal's badge ("Limited time deal"), or '' when there is no deal.
	 * @param string     $deal_ends      When the deal ends, as Amazon gives it (ISO 8601), or ''.
	 * @param string     $deal_access    Who may buy at the deal price: ALL, PRIME_EARLY_ACCESS or PRIME_EXCLUSIVE, or ''.
	 */
	public function __construct(
		public readonly ?float $price_amount = null,
		public readonly ?float $basis_amount = null,
		public readonly string $basis_type = '',
		public readonly ?float $savings_amount = null,
		public readonly string $seller = '',
		public readonly string $condition = '',
		public readonly bool $buy_box = false,
		public readonly string $deal_badge = '',
		public readonly string $deal_ends = '',
		public readonly string $deal_access = '',
	) {}
}
