<?php
/**
 * The sample product the Display preview draws.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Display;

use Affibloom\Domain\Box\BoxOverrides;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductType;

/**
 * Lasso's sample for Settings → Display (F5 R95; settings-display/class-ajax.php 117-197): "Essentialism", $15.99 with a
 * $18.99 basis price, Prime, badge "Our Pick", its description, the demo Pros & Cons and a second button. The second
 * button's link is `#` (D9; Lasso's points to its own site). Lasso builds it as shortcode attributes on a demo item; here
 * the same attributes are BoxOverrides on a product that exists nowhere.
 */
final class PreviewProduct {

	/**
	 * The sample product.
	 *
	 * @param string $image The sample's picture (Affibloom's copy of Lasso's essentialism.jpg).
	 */
	public static function product( string $image ): Product {
		return new Product(
			id: 0,
			name: 'Essentialism: The Disciplined Pursuit of Less',
			// The preview's links are inert (R98); a product link must still be a valid address.
			url: AffiliateUrl::from_string( 'https://example.com/essentialism' ),
			type: ProductType::Other,
			new_tab: true,
			nofollow: true,
			sponsored: false,
			show_disclosure: true,
			image_url: $image,
			slug: 'essentialism',
		);
	}

	/**
	 * Lasso's attributes for the sample (R95): Single draws Pros & Cons, Grid does not.
	 *
	 * @param string $theme The theme being previewed.
	 * @param bool   $grid  Whether the Grid type is previewed.
	 */
	public static function overrides( string $theme, bool $grid ): BoxOverrides {
		return BoxOverrides::from_shortcode(
			array(
				'id'             => 0,
				'theme'          => $theme,
				'price'          => '$15.99',
				'prime'          => 'true',
				'description'    => "The Way of the Essentialist isn't about getting more done in less time. It's about getting only the right things done.",
				'badge'          => 'Our Pick',
				'show_pros_cons' => $grid ? 'false' : 'true',
				'basis_price'    => '$18.99',
				'secondary_url'  => '#',
				'fields'         => 'demo',
			)
		);
	}
}
