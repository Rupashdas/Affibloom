<?php
/**
 * A shortcode box's default anchor.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Shortcode;

/**
 * The container's `id` (F31 A39, D5): the box's `anchor_id`, else `affibloom-anchor-id-<product>-<n>` with the box's
 * number among the page's `[affibloom]` boxes (Lasso's `uniqid()` changes on every load; D5 makes it deterministic; the
 * `affibloom-` prefix is ADR-6's, docs/ui/tokens.md).
 */
final class AnchorId {

	/**
	 * The anchor.
	 *
	 * @param string $given   The box's `anchor_id`; '' none.
	 * @param int    $product The product's ID.
	 * @param int    $number  The box's number on the page, from 1.
	 */
	public static function of( string $given, int $product, int $number ): string {
		return '' !== $given ? $given : 'affibloom-anchor-id-' . $product . '-' . $number;
	}
}
