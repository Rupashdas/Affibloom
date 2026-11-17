<?php
/**
 * One URL variant of a product.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Variant;

/**
 * A product's extra address (F27 R1): a name for the editor and a key, the permalink slug or the Amazon tracking ID.
 */
final class Variant {

	/**
	 * Builds a variant.
	 *
	 * @param int         $id     Its row's ID (0 before it is stored; new after every save, R16).
	 * @param int         $parent The product it belongs to.
	 * @param string      $name   The editor's label.
	 * @param string      $key    The permalink slug or the tracking ID.
	 * @param VariantKind $kind   What the key is.
	 */
	public function __construct(
		public readonly int $id,
		public readonly int $parent,
		public readonly string $name,
		public readonly string $key,
		public readonly VariantKind $kind,
	) {}
}
