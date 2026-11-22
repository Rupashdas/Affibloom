<?php
/**
 * What the extension API may do with Affibloom's block.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Extension;

use Affibloom\Application\Groups\GroupService;
use Affibloom\Domain\Display\ShortcodeFormat;
use Affibloom\Domain\Shortcode\ShortcodeAttributes;

/**
 * What the extension API may do with the Product box block (`affibloom/product-box`): turn `[affibloom …]`'s
 * attributes into the block's, as its shortcode box does (Task 24C), and name what the block would lose, so a helper
 * converting other content keeps a box exactly (the migrator's conversion, F10 D16; Task LM2).
 */
final class BlockAccess {

	/**
	 * Builds the access.
	 *
	 * @param GroupService $groups The groups, for a group display's ID.
	 */
	public function __construct( private GroupService $groups ) {}

	/**
	 * The block's attributes for a display named by `[affibloom …]`'s attributes, and the names it would lose; null for
	 * attributes that name no product, group or table, or a group that does not exist.
	 *
	 * @param array<int|string, mixed> $attributes The shortcode's attributes, as WordPress parses them.
	 * @return array{attributes: array<string, mixed>, lost: list<string>}|null
	 */
	public function from_shortcode( array $attributes ): ?array {
		$repaired = ShortcodeAttributes::repaired( $attributes );
		$display  = ShortcodeFormat::read( $repaired );
		if ( null === $display ) {
			return null;
		}
		$group = '' === $display['group'] ? 0 : $this->groups->id_by_slug( $display['group'] );
		if ( null === $group ) {
			return null;
		}

		// The block saves only what differs from its defaults (block.json: IDs 0, no overrides), as the editor does.
		$block = array_filter(
			array(
				'type'      => $display['type'],
				'productId' => $display['product'],
				'groupId'   => $group,
				'tableId'   => $display['table'],
				'overrides' => $display['overrides'],
			),
			static fn ( mixed $value ): bool => 0 !== $value && array() !== $value
		);

		return array(
			'attributes' => $block + ShortcodeFormat::schema( $repaired ),
			'lost'       => ShortcodeFormat::lost( $display['type'], $repaired ),
		);
	}
}
