<?php
/**
 * Loads what a post's single boxes draw, once for the post (Task 37B).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Shortcode;

use Affibloom\Domain\Amazon\AmazonProductRepository;
use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Domain\Shortcode\DisplayIds;

/**
 * Loads what a post's single boxes draw, once for the post (Task 37B): the products its blocks and shortcodes name,
 * then the Amazon rows of those that are Amazon products. Each box then reads them from memory; without this, every
 * box cost two or three queries (its post, its meta, its Amazon row), 20 to 30 for the ten boxes of a guide.
 */
final class DisplayPreload {

	/**
	 * Builds the service.
	 *
	 * @param ProductRepository       $products The products.
	 * @param AmazonProductRepository $amazon   Amazon's stored data.
	 */
	public function __construct( private ProductRepository $products, private AmazonProductRepository $amazon ) {}

	/**
	 * Loads what the content's single boxes will draw.
	 *
	 * @param string $content A post's content, before its blocks and shortcodes render.
	 */
	public function content( string $content ): void {
		$ids = DisplayIds::products( $content );
		if ( array() === $ids ) {
			return;
		}
		$this->products->prime( $ids );

		$keys = array();
		foreach ( $ids as $id ) {
			$product = $this->products->get( $id );
			if ( null !== $product && ProductType::Amazon === $product->type && '' !== $product->asin ) {
				$keys[] = array( $product->asin, $product->amazon_country );
			}
		}
		if ( array() !== $keys ) {
			$this->amazon->prime( $keys );
		}
	}
}
