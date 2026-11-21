<?php
/**
 * The sample products the Display preview draws.
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
 * Lasso's samples for Settings → Display (F5 R95; settings.js 3558-3607, settings-display/class-ajax.php 117-197):
 * "Essentialism", $15.99 with a $18.99 basis price, Prime, badge "Our Pick", its description, the demo Pros & Cons and a
 * second button; Grid and List add "Atomic Habits" and "Effortless" with their own prices and no badge (Grid: one per
 * column, at most 3). The second button's link is `#` (D9; Lasso's points to its own site). Lasso builds each as
 * shortcode attributes on a demo item; here the same attributes are BoxOverrides on a product that exists nowhere.
 */
final class PreviewProduct {

	/**
	 * Lasso's samples, in its order: the picture's file name (Affibloom's copies of Lasso's), name, price, basis price,
	 * description and badge (`hide`: none).
	 *
	 * @var list<array{image: string, name: string, price: string, basis: string, description: string, badge: string}>
	 */
	private const SAMPLES = array(
		array(
			'image'       => 'essentialism.jpg',
			'name'        => 'Essentialism: The Disciplined Pursuit of Less',
			'price'       => '$15.99',
			'basis'       => '$18.99',
			'description' => "The Way of the Essentialist isn't about getting more done in less time. It's about getting only the right things done.",
			'badge'       => 'Our Pick',
		),
		array(
			'image'       => 'atomichabits.jpg',
			'name'        => 'Atomic Habits: A Proven Way to Build Good Habits',
			'price'       => '$10.99',
			'basis'       => '$12.99',
			'description' => 'No matter your goals, Atomic Habits offers a proven framework for improving every day. If you’re having trouble changing your habits, the problem isn’t you - it’s your system. This book will transform the way you think about progress and give you the tools to do it.',
			'badge'       => 'hide',
		),
		array(
			'image'       => 'effortless.jpg',
			'name'        => 'Effortless: Make It Easier to Do What Matters Most',
			'price'       => '$14.99',
			'basis'       => '$16.99',
			'description' => 'If you’ve ever felt like you’re running faster but not moving closer to your goals, this book’s for you. It offers actionable advice for making the most vital activities the easiest ones, so you can get the results you want without burnout.',
			'badge'       => 'hide',
		),
	);

	/**
	 * How many samples a Grid preview draws (R95): one per column, at most the three samples.
	 *
	 * @param int $columns The grid's column count.
	 */
	public static function in_grid( int $columns ): int {
		return min( count( self::SAMPLES ), max( 1, $columns ) );
	}

	/**
	 * How many samples a List preview draws (R95): all three.
	 */
	public static function in_list(): int {
		return count( self::SAMPLES );
	}

	/**
	 * A sample product.
	 *
	 * @param string $images The folder holding the samples' pictures, ending in `/`.
	 * @param int    $sample The sample, from 0 (Essentialism).
	 */
	public static function product( string $images, int $sample = 0 ): Product {
		$values = self::SAMPLES[ $sample ];

		return new Product(
			id: 0,
			name: $values['name'],
			// The preview's links are inert (R98); a product link must still be a valid address.
			url: AffiliateUrl::from_string( 'https://example.com/' . basename( $values['image'], '.jpg' ) ),
			type: ProductType::Other,
			new_tab: true,
			nofollow: true,
			sponsored: false,
			show_disclosure: true,
			image_url: $images . $values['image'],
			slug: basename( $values['image'], '.jpg' ),
		);
	}

	/**
	 * Lasso's attributes for a sample (R95): Single draws Pros & Cons, Grid does not.
	 *
	 * @param string $theme  The theme being previewed.
	 * @param bool   $grid   Whether the Grid type is previewed.
	 * @param int    $sample The sample, from 0 (Essentialism).
	 */
	public static function overrides( string $theme, bool $grid, int $sample = 0 ): BoxOverrides {
		$values = self::SAMPLES[ $sample ];

		return BoxOverrides::from_shortcode(
			array(
				'id'             => 0,
				'theme'          => $theme,
				'price'          => $values['price'],
				'prime'          => 'true',
				'description'    => $values['description'],
				'badge'          => $values['badge'],
				'show_pros_cons' => $grid ? 'false' : 'true',
				'basis_price'    => $values['basis'],
				'secondary_url'  => '#',
				'fields'         => 'demo',
			)
		);
	}
}
