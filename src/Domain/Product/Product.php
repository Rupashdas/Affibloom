<?php
/**
 * An affiliate product: what a box shows and where its buttons go.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Product;

/**
 * An affiliate product: what a box shows and where its buttons go. Later tasks add its other fields as their
 * screens need them.
 */
final class Product {

	/**
	 * Builds a product. Use draft() for a new one.
	 *
	 * @param int|null     $id   Post ID; null until it is saved.
	 * @param string       $name Name, shown as the box title.
	 * @param AffiliateUrl $url  Primary destination.
	 * @param ProductType  $type Kind of merchant.
	 */
	public function __construct(
		public readonly ?int $id,
		public readonly string $name,
		public readonly AffiliateUrl $url,
		public readonly ProductType $type,
	) {}

	/**
	 * A new, unsaved product.
	 *
	 * @param string       $name Name.
	 * @param AffiliateUrl $url  Primary destination.
	 */
	public static function draft( string $name, AffiliateUrl $url ): self {
		return new self( null, $name, $url, ProductType::Other );
	}

	/**
	 * A copy with some properties changed, e.g. the ID once stored.
	 *
	 * @param array<string, mixed> $changes Property name => new value.
	 */
	public function with( array $changes ): self {
		return new self( ...array_merge( get_object_vars( $this ), $changes ) );
	}
}
