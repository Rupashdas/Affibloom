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
	 * @param DisplayKind  $kind      What to draw.
	 * @param Address      $address   Which product.
	 * @param BoxOverrides $overrides The box's own values.
	 */
	public function __construct(
		public readonly DisplayKind $kind,
		public readonly Address $address,
		public readonly BoxOverrides $overrides,
	) {}

	/**
	 * The request in a shortcode's attributes.
	 *
	 * @param array<int|string, mixed>|string $raw The attributes as WordPress parsed them.
	 */
	public static function of( array|string $raw ): self {
		$attributes = ShortcodeAttributes::repaired( $raw );

		return new self( DisplayKind::Single, Address::of( $attributes ), BoxOverrides::from_shortcode( $attributes ) );
	}
}
