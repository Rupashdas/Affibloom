<?php
/**
 * Which product a shortcode names.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Shortcode;

/**
 * The product a shortcode addresses (F31 A1, A2): its `id`, or its `slug`, which wins when it finds a product.
 */
final class Address {

	/**
	 * Builds the address.
	 *
	 * @param int    $id   The product's ID (`id`); 0 none.
	 * @param string $slug The product's slug (`slug`); '' none.
	 */
	public function __construct( public readonly int $id, public readonly string $slug ) {}

	/**
	 * The address in a shortcode's attributes.
	 *
	 * @param array<string, string> $attributes Repaired attributes.
	 */
	public static function of( array $attributes ): self {
		$id = $attributes['id'] ?? '';

		return new self( ctype_digit( $id ) ? (int) $id : 0, trim( $attributes['slug'] ?? '' ) );
	}
}
