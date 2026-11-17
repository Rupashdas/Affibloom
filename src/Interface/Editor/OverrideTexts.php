<?php
/**
 * The words of the editor's Customize Display panel.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Editor;

use Affibloom\Domain\Display\OverrideCatalog;

/**
 * Lasso's labels and help texts for Customize Display (F4 R35-R38; lasso-constant.php 388-713), minus its name: its
 * examples on its own site read example.com, "Lasso Links" read "products", and the reference-guide notice and the
 * brag field's affiliate link are left out (D5, deviation 52). The help texts keep Lasso's `<code>`, `<i>` and `<br/>`.
 */
final class OverrideTexts {

	/**
	 * The panel's fields per type, with their label, help text and kind, for the editor.
	 *
	 * @return array<string, list<array{name: string, label: string, help: string, kind: string, on: bool}>>
	 */
	public static function catalog(): array {
		$catalog = array();
		foreach ( OverrideCatalog::all() as $type => $fields ) {
			foreach ( $fields as $field ) {
				[ $label, $help ]   = self::words( $type, $field );
				$catalog[ $type ][] = array(
					'name'  => $field,
					'label' => $label,
					'help'  => $help,
					'kind'  => OverrideCatalog::kind( $field ),
					'on'    => OverrideCatalog::on_by_default( $field ),
				);
			}
		}

		return $catalog;
	}

	/**
	 * One field's label and help text; a few help texts differ by type, as Lasso's do.
	 *
	 * @param string $type  The display type.
	 * @param string $field The shortcode attribute.
	 * @return array{0: string, 1: string}
	 */
	private static function words( string $type, string $field ): array {
		$group  = in_array( $type, array( 'grid', 'list' ), true );
		$themes = __( 'Override the theme. Supported theme values are: <i>Cactus, Cutter, Lab, Llama, Money, Splash</i>', 'affibloom' );

		return match ( $field ) {
			'title'           => array( __( 'Product title', 'affibloom' ), $group ? __( 'Hide the product title by <code>hide</code> value.', 'affibloom' ) : __( 'Override the product title.<br/>Hide the product title by <code>hide</code> value.', 'affibloom' ) ),
			'title_url'       => array( __( 'Title url', 'affibloom' ), __( 'Customize the title link destination. Leave blank for the default.<br/>Example: https://example.com', 'affibloom' ) ),
			'title_type'      => array( __( 'Title tag', 'affibloom' ), __( 'Set the title tag. Common values are: <i>H1, H2, H3, H4</i>', 'affibloom' ) ),
			'description'     => array( __( 'Product description', 'affibloom' ), $group ? __( 'Hide the product description by <code>hide</code> value.', 'affibloom' ) : __( 'Override the product description<br/>Hide the product description by <code>hide</code> value.', 'affibloom' ) ),
			'badge'           => array( __( 'Badge', 'affibloom' ), $group ? __( 'Hide the badge by <code>hide</code> value.', 'affibloom' ) : __( 'Override the display badge.<br/>Hide the badge by <code>hide</code> value.', 'affibloom' ) ),
			'brag'            => array( __( 'Brag', 'affibloom' ), __( 'Show the brag line on your display by <code>true</code> value.', 'affibloom' ) ),
			'price'           => array( __( 'Show/Hide the price', 'affibloom' ), '' ),
			'field'           => array( __( 'Show/Hide all Fields', 'affibloom' ), '' ),
			'rating'          => array( __( 'Show/Hide the Primary Rating', 'affibloom' ), '' ),
			'theme'           => array( __( 'Theme', 'affibloom' ), $themes ),
			'primary_url'     => 'button' === $type
				? array( __( 'Button url', 'affibloom' ), __( 'Override the button url.<br/>Example: https://example.com', 'affibloom' ) )
				: array( __( 'First button url', 'affibloom' ), __( 'Override the first button url.<br/>Example: https://example.com', 'affibloom' ) ),
			'primary_text'    => 'button' === $type
				? array( __( 'Button text', 'affibloom' ), __( 'Override the button text.', 'affibloom' ) )
				: array( __( 'First button text', 'affibloom' ), __( 'Override the first button text.', 'affibloom' ) ),
			'secondary_url'   => array( __( 'Second button url', 'affibloom' ), __( 'Override the second button url.', 'affibloom' ) ),
			'secondary_text'  => array( __( 'Second button text', 'affibloom' ), __( 'Override the second button text.', 'affibloom' ) ),
			'image_url'       => array( __( 'Image url', 'affibloom' ), __( 'Override the image url.<br/>Example: https://example.com/image.png', 'affibloom' ) ),
			'disclosure_text' => array( __( 'Disclosure text', 'affibloom' ), __( 'Override the disclosure text.', 'affibloom' ) ),
			'anchor_id'       => array( __( 'Anchor id', 'affibloom' ), __( 'Override the anchor id.<br/>Example: the-anchor-link-id', 'affibloom' ) ),
			'button_type'     => array( __( 'Button type', 'affibloom' ), __( 'Set this value to show either the Secondary(secondary) or Primary(primary) button. This defaults to "primary".', 'affibloom' ) ),
			'columns'         => array(
				__( 'Columns', 'affibloom' ),
				'gallery' === $type
					? __( 'Sets the Gallery to display five products per row. Ideal for wide layouts. This can also be set 2, 3, 4, and 5.', 'affibloom' )
					: __( 'Sets the Grid to display three products per row. Ideal for wide layouts. This can also be set to 1 for a single display per-row and the limit can be set is 5.', 'affibloom' ),
			),
			'compact'         => array( __( 'Compact', 'affibloom' ), __( 'This will hide the description and all fields giving you a much more compact display by <code>hide</code> value.', 'affibloom' ) ),
			'limit'           => array( __( 'Limit', 'affibloom' ), __( 'Show just the top X products in a group.', 'affibloom' ) ),
			'bullets'         => array( __( 'Bullet', 'affibloom' ), __( 'Override the bullet type. To hide the bullet use <code>hide</code>. All supported values are: <i>decimal, alpha, roman, square, circle, hide</i>', 'affibloom' ) ),
			'disclosure'      => array( __( 'Show/Hide the Disclosure', 'affibloom' ), '' ),
			'data_nosnippet'  => array( __( 'data-nosnippet HTML attribute', 'affibloom' ), __( 'Keeps the display out of search result snippets.', 'affibloom' ) ),
			default           => array( $field, '' ),
		};
	}
}
