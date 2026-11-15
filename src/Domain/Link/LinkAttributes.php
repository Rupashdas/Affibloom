<?php
/**
 * The attributes a product's links carry in a box: Lasso's, in Lasso's order, under Affibloom's names.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Link;

/**
 * The attributes of a product's links in a box (F5 R38, R39, D3; Lasso_URL::get_attrs and render_attributes,
 * Lasso_Affiliate_Link 442-458): `target`, `href`, the trackable flag, the product's ID, its name, `title`, then `rel`.
 * Lasso's `data-lasso-*` names become `data-affibloom-*` (ADR-6): `data-lasso-lid` (the product) is
 * `data-affibloom-id`, the ID the click counter reads (F12 D3). Lasso's `data-lasso-id` (a box's link location) is not
 * printed until a box knows its location (F21). Values are returned raw; the template escapes them.
 */
final class LinkAttributes {

	/**
	 * Links of one product.
	 *
	 * @param int    $product_id The product's ID.
	 * @param string $name       Its name, as the box shows it.
	 */
	public function __construct( private readonly int $product_id, private readonly string $name ) {}

	/**
	 * The primary link (image, title, button 1): Lasso's `rel` and `target` from the product's own switches.
	 *
	 * @param string $href      Where it goes.
	 * @param bool   $new_tab   Open New Window / Tab.
	 * @param bool   $nofollow  NoFollow / NoIndex.
	 * @param bool   $sponsored Sponsored.
	 * @return array<string, string>
	 */
	public function primary( string $href, bool $new_tab, bool $nofollow, bool $sponsored ): array {
		return $this->attributes( $href, $new_tab, self::rel( $nofollow, $new_tab, $sponsored ) );
	}

	/**
	 * A secondary button's link: `sponsored` only when the product is sponsored and the button's own NoFollow is on;
	 * without text Lasso prints it with neither `href` nor `target`.
	 *
	 * @param string $href      Where it goes.
	 * @param string $text      The button's text.
	 * @param bool   $new_tab   The button's Open New Window / Tab.
	 * @param bool   $nofollow  The button's NoFollow / NoIndex.
	 * @param bool   $sponsored The product's Sponsored.
	 * @return array<string, string>
	 */
	public function secondary( string $href, string $text, bool $new_tab, bool $nofollow, bool $sponsored ): array {
		$attributes = $this->attributes( $href, $new_tab, self::rel( $nofollow, $new_tab, $sponsored && $nofollow ) );
		if ( '' === trim( $text ) ) {
			unset( $attributes['target'], $attributes['href'] );
		}

		return $attributes;
	}

	/**
	 * Lasso's `rel`: nofollow, noopener for a new window (against tabnabbing), sponsored; empty when none applies.
	 *
	 * @param bool $nofollow  NoFollow / NoIndex.
	 * @param bool $new_tab   Open New Window / Tab.
	 * @param bool $sponsored Sponsored.
	 */
	private static function rel( bool $nofollow, bool $new_tab, bool $sponsored ): string {
		$tokens = array_keys(
			array_filter(
				array(
					'nofollow'  => $nofollow,
					'noopener'  => $new_tab,
					'sponsored' => $sponsored,
				)
			)
		);

		return implode( ' ', $tokens );
	}

	/**
	 * The shared attributes, in Lasso's order. An empty name is replaced by the link's domain label, capitalised, in
	 * the name attribute only (Lasso_Helper::get_name_of_domain: the host without "www.", up to its first dot).
	 *
	 * @param string $href    Where the link goes.
	 * @param bool   $new_tab Open New Window / Tab.
	 * @param string $rel     The `rel` value.
	 * @return array<string, string>
	 */
	private function attributes( string $href, bool $new_tab, string $rel ): array {
		$name = $this->name;
		if ( '' === trim( $name ) ) {
			$host = (string) parse_url( $href, PHP_URL_HOST ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Domain code does not call WordPress.
			$name = ucfirst( explode( '.', str_replace( 'www.', '', $host ) )[0] );
		}

		return array(
			'target'                       => $new_tab ? '_blank' : '_self',
			'href'                         => $href,
			'data-affibloom-box-trackable' => 'true',
			'data-affibloom-id'            => (string) $this->product_id,
			'data-affibloom-name'          => $name,
			'title'                        => $this->name,
			'rel'                          => $rel,
		);
	}
}
