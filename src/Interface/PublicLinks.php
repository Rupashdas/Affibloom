<?php
/**
 * Products' public links on this site.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface;

use Affibloom\Domain\Cloak\PublicLink;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Settings\SettingsRepository;

/**
 * The public link (F24 R13, R14, D9) with this site's home address and Cloaked Link Prefix, for every reader that
 * prints one: the product page and its save (CL3), the box and text links (CL6), the Dashboard (CL7), the editors'
 * link search (CL8, CL9).
 */
final class PublicLinks {

	/**
	 * Builds the links.
	 *
	 * @param SettingsRepository $settings The Cloaked Link Prefix.
	 */
	public function __construct( private SettingsRepository $settings ) {}

	/**
	 * The product's public link.
	 *
	 * @param Product $product The product.
	 */
	public function for( Product $product ): string {
		return PublicLink::for( $product, home_url(), $this->settings->get()->string( 'link_prefix' ) );
	}

	/**
	 * Each product's public link by its ID, for a list (the Dashboard, CL7).
	 *
	 * @param list<Product> $products The products.
	 * @return array<int, string>
	 */
	public function of( array $products ): array {
		$links = array();
		foreach ( $products as $product ) {
			$links[ (int) $product->id ] = $this->for( $product );
		}

		return $links;
	}

	/**
	 * What a slug's cloaked address would be ("<home>/<prefix>/" before the slug), for the Permalink field's live line.
	 */
	public function base(): string {
		$prefix = trim( $this->settings->get()->string( 'link_prefix' ), '/' );

		return rtrim( home_url(), '/' ) . '/' . ( '' === $prefix ? '' : $prefix . '/' );
	}
}
