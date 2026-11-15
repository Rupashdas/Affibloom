<?php
/**
 * Amazon's data for one ASIN on one store, as Affibloom keeps it.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Amazon;

/**
 * Amazon's data for one ASIN on one store (C1 D5; Lasso's lasso_amazon_products row, R26): what products with that ASIN
 * read their Amazon name, image, price and stock from. Every lookup and write carries the country (D5 fixes Lasso's
 * ASIN-only update, R29). `source` says where it came from: "creators" (the Creators API) or "lasso" (Task 26's copy).
 */
final class AmazonProduct {

	/**
	 * Builds the row.
	 *
	 * @param string       $asin            The ASIN, upper case.
	 * @param string       $country         The store's country code.
	 * @param string       $name            Amazon's title.
	 * @param string       $price           The display price, or ''.
	 * @param string       $currency        The price's ISO currency, Lasso's default "USD".
	 * @param string       $base_url        The product's page on the store, without a tag.
	 * @param string       $monetized_url   The link a product stores for it (the tag rule applied).
	 * @param string       $image           The image link (never downloaded, R30).
	 * @param bool         $out_of_stock    Whether Amazon cannot sell it now.
	 * @param string       $savings_amount  The display saving, or ''.
	 * @param int          $savings_percent The saving in per cent, or 0.
	 * @param string       $savings_basis   The display price the saving is from, or ''.
	 * @param list<string> $features        The feature lines.
	 * @param string       $source          "creators" or "lasso".
	 * @param string       $last_updated    When it was fetched, "Y-m-d H:i:s" in UTC.
	 * @param bool         $is_prime        Lasso's Prime flag, history only: no fetch writes it (R14), Task 26 copies it, and
	 *                                      a newer row keeps the older one's (over()). The box's Prime logo follows the ASIN
	 *                                      (R46), not this.
	 */
	public function __construct(
		public readonly string $asin,
		public readonly string $country,
		public readonly string $name,
		public readonly string $price,
		public readonly string $currency,
		public readonly string $base_url,
		public readonly string $monetized_url,
		public readonly string $image,
		public readonly bool $out_of_stock,
		public readonly string $savings_amount,
		public readonly int $savings_percent,
		public readonly string $savings_basis,
		public readonly array $features,
		public readonly string $source,
		public readonly string $last_updated,
		public readonly bool $is_prime = false
	) {}

	/**
	 * The row for an item just fetched from the Creators API.
	 *
	 * @param CatalogItem $item          The item.
	 * @param Asin        $asin          The ASIN that was asked for: the row is kept under it, so the next add of that
	 *                                   ASIN finds it, even when Amazon answers with another (a variation's parent).
	 * @param string      $country       The store it came from.
	 * @param string      $monetized_url The link the product stores for it.
	 * @param string      $now           The time, "Y-m-d H:i:s" in UTC.
	 */
	public static function from_item( CatalogItem $item, Asin $asin, string $country, string $monetized_url, string $now ): self {
		return new self(
			$asin->value,
			$country,
			$item->title,
			// Lasso stores a price of "0" as none (R27).
			'0' === $item->price ? '' : $item->price,
			'' === $item->currency ? 'USD' : $item->currency,
			'https://' . Marketplace::host( $country ) . '/dp/' . $asin->value,
			$monetized_url,
			$item->image,
			! $item->availability->in_stock,
			$item->savings,
			$item->savings_percent,
			$item->basis,
			$item->features,
			'creators',
			$now
		);
	}

	/**
	 * Whether Lasso would store it (update_amazon_product_in_db, R27): an ASIN, a title, and an image that is a web link
	 * or a data: image. A product shows only an https one (is_image_link()).
	 */
	public function complete(): bool {
		return '' !== $this->asin && '' !== $this->name && 1 === preg_match( '#^(https?://\S+|data:image/)#i', $this->image );
	}

	/**
	 * This row stored over an older one for the same ASIN and store: Lasso keeps the old price and image when the new
	 * ones are empty, and replaces the rest (R27); the Prime flag is the older row's, since no fetch knows it (R14).
	 *
	 * @param AmazonProduct|null $older What was stored, if anything.
	 */
	public function over( ?AmazonProduct $older ): self {
		if ( null === $older ) {
			return $this;
		}

		return new self(
			$this->asin,
			$this->country,
			$this->name,
			'' === $this->price ? $older->price : $this->price,
			$this->currency,
			$this->base_url,
			$this->monetized_url,
			'' === $this->image ? $older->image : $this->image,
			$this->out_of_stock,
			$this->savings_amount,
			$this->savings_percent,
			$this->savings_basis,
			$this->features,
			$this->source,
			$this->last_updated,
			$older->is_prime
		);
	}

	/**
	 * The same row for a product stored with another link (Lasso re-stores a reused row with the new link, R32).
	 *
	 * @param string $monetized_url The link the new product stores.
	 */
	public function linked( string $monetized_url ): self {
		return new self(
			$this->asin,
			$this->country,
			$this->name,
			$this->price,
			$this->currency,
			$this->base_url,
			$monetized_url,
			$this->image,
			$this->out_of_stock,
			$this->savings_amount,
			$this->savings_percent,
			$this->savings_basis,
			$this->features,
			$this->source,
			$this->last_updated,
			$this->is_prime
		);
	}

	/**
	 * Whether an image may be shown from a product: a secure link (the admin and the box load it as is).
	 *
	 * @param string $image An image link.
	 */
	public static function is_image_link( string $image ): bool {
		return 1 === preg_match( '#^https://[^\s/?\#]+/\S*$#i', $image );
	}
}
