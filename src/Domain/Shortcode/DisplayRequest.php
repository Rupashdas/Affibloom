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
}
