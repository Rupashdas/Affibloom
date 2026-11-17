<?php
/**
 * How a link is shown in the content.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\LinkIndex;

/**
 * Lasso's display types for anchors (F21 R25, D1: its strings, so the opportunity queries port as written). Boxes
 * and group displays add theirs in Task 35B.
 */
final class DisplayType {

	/** An anchor with text. */
	public const TEXT = 'Text';

	/** An anchor around an image. */
	public const IMAGE = 'Image-only anchor text';
}
