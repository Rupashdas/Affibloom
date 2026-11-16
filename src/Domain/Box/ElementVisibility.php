<?php
/**
 * Which of a box's elements show: the display type's element switches and the box's own hides.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Box;

use Affibloom\Domain\Display\Elements;

/**
 * A box's element visibility (F5 R60-R63; Lasso's Helper::compose_display_element_visibility): the Single element map,
 * then the box's own hides (`badge`, `title`, `description`, `image_url`, `primary_text` or `secondary_text` set to
 * `hide`, `price`, `fields` or `field` set to `hide`, and `show_pros_cons` false). Hidden elements are printed and hidden by the wrapper's `data-hide-*` flags, except the
 * title and the description, which are not printed (R61, R63), and a badge with text, which still shows (R62).
 */
final class ElementVisibility {

	/**
	 * Use of().
	 *
	 * @param array<string, bool> $shown Each element key (Elements::KEYS) and whether it shows.
	 */
	private function __construct( private readonly array $shown ) {}

	/**
	 * The visibility of one box.
	 *
	 * @param Elements     $elements   The display type's element switches.
	 * @param BoxOverrides $overrides  The box's own values.
	 * @param bool|null    $show_price The product's Show Price (null when never stored): saved on, it shows the Price
	 *                                 element whatever the site's switch (R28).
	 */
	public static function of( Elements $elements, BoxOverrides $overrides, ?bool $show_price = null ): self {
		$shown = array();
		foreach ( Elements::KEYS as $key ) {
			$shown[ $key ] = $elements->visible( $key );
		}
		$shown['price'] = PriceRule::element_shows( $shown['price'], $show_price );

		$box_hides = array(
			'badge'     => $overrides->badge,
			'title'     => $overrides->title,
			'desc'      => $overrides->description,
			'image'     => $overrides->image_url,
			'primary'   => $overrides->primary_text,
			'secondary' => $overrides->secondary_text,
			'price'     => $overrides->price,
			'proscons'  => $overrides->fields,
		);
		foreach ( $box_hides as $key => $value ) {
			if ( BoxOverrides::hides( $value ) ) {
				$shown[ $key ] = false;
			}
		}
		if ( false === $overrides->show_pros_cons ) {
			$shown['proscons'] = false;
		}

		return new self( $shown );
	}

	/**
	 * Whether an element shows.
	 *
	 * @param string $key One of Elements::KEYS.
	 */
	public function shows( string $key ): bool {
		return $this->shown[ $key ] ?? true;
	}

	/**
	 * The hidden elements, in Lasso's order: the wrapper's `data-hide-*` flags.
	 *
	 * @return list<string>
	 */
	public function hidden(): array {
		return array_keys( array_filter( $this->shown, static fn( bool $shows ): bool => ! $shows ) );
	}

	/**
	 * Whether a badge with text takes the override class that the Badge hide rule skips (R62): the Badge element is
	 * off, so the badge still shows.
	 */
	public function badge_overrides(): bool {
		return ! $this->shows( 'badge' );
	}
}
