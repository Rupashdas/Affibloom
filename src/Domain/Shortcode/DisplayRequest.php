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

	/** The block's types that show a group (F4 D3; the shortcode's `type` with a `group`). */
	private const GROUP_TYPES = array( 'grid', 'list', 'gallery' );

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
	 * Whether a Product box block's attributes name a display (F4 D3): a type the block draws and its target's ID (a
	 * product, or a group for a grid, list or gallery). A block with no `type` is the single box, as blocks saved before
	 * Phase 4 are. The editor's preview answers 400 when they do not (Task 23C).
	 *
	 * @param array<string, mixed> $attributes The block's attributes.
	 */
	public static function names_display( array $attributes ): bool {
		$type = $attributes['type'] ?? '';
		if ( ! is_string( $type ) ) {
			return false;
		}
		if ( in_array( $type, self::GROUP_TYPES, true ) ) {
			return self::id( $attributes, 'groupId' ) > 0;
		}

		return in_array( $type, array( '', 'single', 'button', 'image' ), true ) && self::id( $attributes, 'productId' ) > 0;
	}

	/**
	 * The request a Product box block asks for (F4 D3, Tasks 23C, 23D), as the shortcode would: its product as the single
	 * box, a button or an image, or its group as a grid, list or gallery. The block keeps its group's ID; the shortcode
	 * names a group by its slug, which the caller looks up. Null when the attributes name no display or the group is gone
	 * (the page then prints nothing, F4 edge case 1).
	 *
	 * @param array<string, mixed> $attributes The block's attributes.
	 * @param string               $group_slug The slug of the block's group ('' none), for the group types.
	 */
	public static function from_block( array $attributes, string $group_slug = '' ): ?self {
		if ( ! self::names_display( $attributes ) ) {
			return null;
		}

		$type = (string) ( $attributes['type'] ?? '' );
		if ( in_array( $type, self::GROUP_TYPES, true ) ) {
			return '' === $group_slug ? null : self::of(
				array(
					'type'  => $type,
					'group' => $group_slug,
				)
			);
		}

		$shortcode = array( 'id' => (string) self::id( $attributes, 'productId' ) );
		if ( 'button' === $type || 'image' === $type ) {
			$shortcode['type'] = $type;
		}

		return self::of( $shortcode );
	}

	/**
	 * A numeric attribute as an ID; 0 when it is missing or not a number.
	 *
	 * @param array<string, mixed> $attributes The block's attributes.
	 * @param string               $name       The attribute.
	 */
	private static function id( array $attributes, string $name ): int {
		return is_numeric( $attributes[ $name ] ?? null ) ? (int) $attributes[ $name ] : 0;
	}
}
