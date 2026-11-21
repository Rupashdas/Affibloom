<?php
/**
 * Products as REST arrays.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Domain\Group\Group;
use Affibloom\Domain\Product\Product;
use Affibloom\Interface\PublicLinks;

/**
 * Products as REST arrays.
 */
final class ProductResource {

	/**
	 * Builds the resource.
	 *
	 * @param PublicLinks|null $links The public link (F24, Task CL3); without it, the destination.
	 */
	public function __construct( private ?PublicLinks $links = null ) {}

	/**
	 * The short form returned after adding.
	 *
	 * @param Product $product A saved product.
	 * @return array{id: int, name: string, url: string}
	 */
	public function summary( Product $product ): array {
		return array(
			'id'   => (int) $product->id,
			'name' => $product->name,
			'url'  => $product->url->value(),
		);
	}

	/**
	 * Every field as stored, for the product page to show what was saved (F1 requirements 9 S15) and for quick edit.
	 *
	 * @param Product     $product A saved product.
	 * @param list<Group> $groups  The groups it is in (F23).
	 * @return array<string, mixed>
	 */
	public function full( Product $product, array $groups = array() ): array {
		return $this->summary( $product ) + array(
			'type'                 => $product->type->value,
			'asin'                 => $product->asin,
			'button_text'          => $product->button_text,
			'new_tab'              => $product->new_tab,
			'nofollow'             => $product->nofollow,
			'buttons'              => $product->buttons->to_array(),
			'primary_position'     => $product->buttons->main_position(),
			'theme'                => null === $product->theme ? '' : $product->theme->name(),
			'image_url'            => $product->image_url,
			'image_id'             => $product->image_id,
			'badge'                => $product->badge,
			'price'                => $product->price,
			'show_price'           => $product->show_price,
			'show_features'        => $product->show_features,
			'description'          => $product->description,
			'disclosure'           => $product->disclosure,
			'sponsored'            => $product->sponsored,
			'show_disclosure'      => $product->show_disclosure,
			'prime'                => $product->prime,
			'slug'                 => $product->slug,
			'cloak'                => $product->cloak,
			'detect_opportunities' => $product->detect_opportunities,
			'public_link'          => null === $this->links ? $product->url->value() : $this->links->for( $product ),
			'groups'               => array_map(
				static fn( Group $group ): array => array(
					'id'   => $group->id,
					'name' => $group->name,
				),
				$groups
			),
		);
	}
}
