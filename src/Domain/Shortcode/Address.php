<?php
/**
 * Which product a shortcode names.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Shortcode;

/**
 * The product a shortcode addresses (F31 A1, A2, A6, A7): its `slug`, which wins when it finds a product, else its `id`;
 * when neither finds one, its `amazon_url` (the product with that ASIN and store), then its `geni_url` (the product with
 * that destination).
 */
final class Address {

	/**
	 * Builds the address.
	 *
	 * @param int    $id   The product's ID (`id`); 0 none.
	 * @param string $slug The product's slug (`slug`); '' none.
	 * @param string $amazon_url An Amazon link naming the product (`amazon_url`); '' none.
	 * @param string $destination The product's destination (`geni_url`); '' none.
	 */
	public function __construct(
		public readonly int $id,
		public readonly string $slug,
		public readonly string $amazon_url = '',
		public readonly string $destination = '',
	) {}

	/**
	 * The address in a shortcode's attributes.
	 *
	 * @param array<string, string> $attributes Repaired attributes.
	 */
	public static function of( array $attributes ): self {
		$id = $attributes['id'] ?? '';

		return new self(
			ctype_digit( $id ) ? (int) $id : 0,
			trim( $attributes['slug'] ?? '' ),
			trim( $attributes['amazon_url'] ?? '' ),
			trim( $attributes['geni_url'] ?? '' )
		);
	}
}
