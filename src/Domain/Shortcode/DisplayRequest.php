<?php
/**
 * What one shortcode asks to draw.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Shortcode;

use Affibloom\Domain\Box\BoxOverrides;

/**
 * One `[affibloom]` request (F31): the display kind, the address and the box's overrides in Lasso's attribute language
 * (D2: one parser, BoxOverrides::from_shortcode). Phase 4's block builds one too.
 */
final class DisplayRequest {

	/**
	 * Builds the request.
	 *
	 * @param DisplayKind           $kind      What to draw.
	 * @param Address               $address   Which product.
	 * @param BoxOverrides          $overrides The box's own values.
	 * @param bool                  $secondary `button_type="secondary"`: a lone button draws the second button (A34).
	 * @param array<string, string> $attributes The repaired attributes, for the kinds that read their own (`label`,
	 *                                          `value`, `image_size`, …).
	 */
	public function __construct(
		public readonly DisplayKind $kind,
		public readonly Address $address,
		public readonly BoxOverrides $overrides,
		public readonly bool $secondary = false,
		public readonly array $attributes = array(),
	) {}

	/**
	 * The request in a shortcode's attributes.
	 *
	 * @param array<int|string, mixed>|string $raw The attributes as WordPress parsed them.
	 */
	public static function of( array|string $raw ): self {
		$attributes = ShortcodeAttributes::repaired( $raw );
		$kind       = DisplayKind::of( $attributes );
		$secondary  = 'secondary' === strtolower( $attributes['button_type'] ?? '' );
		// F30 R30: a lone secondary button takes its URL from `primary_url` (Lasso reads no `secondary_url` there).
		$values = DisplayKind::Button === $kind && $secondary
			? array( 'secondary_url' => $attributes['primary_url'] ?? '' ) + array_diff_key( $attributes, array_flip( array( 'primary_url', 'secondary_url' ) ) )
			: $attributes;

		return new self(
			$kind,
			Address::of( $attributes ),
			BoxOverrides::from_shortcode( $values ),
			$secondary,
			$attributes
		);
	}

	/**
	 * The request a Product box block asks for (F4 D3, Task 23C), as the shortcode would: its `type` and its product.
	 * Null when the block names no product or a type the block cannot draw yet (the editor's preview answers 400).
	 * A block with no `type` is the single box, as blocks saved before Phase 4 are.
	 *
	 * @param array<string, mixed> $attributes The block's attributes.
	 */
	public static function from_block( array $attributes ): ?self {
		$type    = $attributes['type'] ?? '';
		$product = is_numeric( $attributes['productId'] ?? null ) ? (int) $attributes['productId'] : 0;
		if ( $product <= 0 || ! is_string( $type ) || ! in_array( $type, array( '', 'single', 'button', 'image' ), true ) ) {
			return null;
		}

		$shortcode = array( 'id' => (string) $product );
		if ( 'button' === $type || 'image' === $type ) {
			$shortcode['type'] = $type;
		}

		return self::of( $shortcode );
	}
}
