<?php
/**
 * Prints the Product structured data of the first box in a post that asks for it.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Front;

use Affibloom\Application\Box\BoxBuilder;
use Affibloom\Application\Box\RepointResolver;
use Affibloom\Application\Box\SchemaBuilder;
use Affibloom\Domain\Amazon\AmazonProductRepository;
use Affibloom\Domain\Fields\ProductFields;
use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Domain\Settings\SettingsRepository;
use Affibloom\Domain\Shortcode\ShortcodeAttributes;
use Affibloom\Module;
use WP_Post;

/**
 * `wp_head` (F5 R90, AC17; F31 R29, D12; Lasso's lasso_schema_markup_output): the first box in the post's content, a
 * Product box block with `schemaReview` or `schemaProsCons` on or an `[affibloom id]` with `schema_review="enable"` or
 * `schema_pros_cons="enable"` (Task 18S14), prints one `<script type="application/ld+json">`, once per post. Lasso
 * reads the product's stored name, picture, price, Primary Rating and Pros / Cons, not the box's overrides; the price
 * and currency attributes replace the price and currency. `[lasso]` joins when Affibloom draws it (Task 27A).
 */
final class SchemaMarkup implements Module {

	/**
	 * The block's name.
	 */
	private const BLOCK = 'affibloom/product-box';

	/**
	 * The shortcodes read (Task 27A adds `lasso` once Affibloom draws it).
	 */
	private const SHORTCODES = array( 'affibloom' );

	/**
	 * Builds the printer.
	 *
	 * @param RepointResolver         $products Which product a box draws.
	 * @param ProductRepository       $catalog  The products, for their fields.
	 * @param AmazonProductRepository $amazon   The stored Amazon data, for the currency.
	 * @param SettingsRepository      $settings The site's settings, for the default picture.
	 * @param BoxBuilder              $builder  The box engine, for the picture rule.
	 */
	public function __construct(
		private readonly RepointResolver $products,
		private readonly ProductRepository $catalog,
		private readonly AmazonProductRepository $amazon,
		private readonly SettingsRepository $settings,
		private readonly BoxBuilder $builder,
	) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'wp_head', array( $this, 'print' ) );
	}

	/**
	 * Prints the JSON-LD, or nothing.
	 */
	public function print(): void {
		$post = get_post();
		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$box = self::first_schema_box( parse_blocks( $post->post_content ) );
		if ( null === $box ) {
			return;
		}
		$product = $this->products->product( $box['id'] );
		if ( null === $product ) {
			return;
		}

		$fields = $this->catalog->fields( (int) $product->id ) ?? ProductFields::from_array( array() );
		$lines  = static function ( int $id ) use ( $fields ): array {
			foreach ( $fields->all() as $row ) {
				if ( $id === $row->field_id ) {
					return explode( "\n", str_replace( "\r", '', $row->value ) );
				}
			}
			return array();
		};
		$rating = '';
		foreach ( $fields->all() as $row ) {
			if ( ProductFields::RATING_ID === $row->field_id && $row->visible ) {
				$rating = trim( $row->value );
			}
		}
		$stored   = ProductType::Amazon === $product->type && '' !== $product->asin ? $this->amazon->find( $product->asin, $product->amazon_country ) : null;
		$currency = '' !== $box['currency'] ? $box['currency'] : ( $stored->currency ?? 'USD' );
		$price    = '' !== $box['price'] ? $box['price'] : $product->price;

		$schema = SchemaBuilder::build(
			$product->name,
			$this->builder->picture( $product, $this->settings->get() ),
			$price,
			$currency,
			$rating,
			$lines( 2 ),
			$lines( 3 ),
			(string) get_the_author_meta( 'display_name', (int) $post->post_author ),
			$box['review'],
			$box['pros_cons']
		);

		echo PHP_EOL . '<script type="application/ld+json" class="affibloom-schema-markup-output">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ) . '</script>' . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON, its "<" escaped (JSON_HEX_TAG).
	}

	/**
	 * The first box, in content order and nested blocks included, that enables Review or Pro/Con schema: its product
	 * ID, which schema it asks for and its price and currency ('' the product's). Lasso stops at the first box that
	 * asks; one whose product does not draw prints nothing (the block's rule since F5, kept for the shortcode).
	 *
	 * @param array<int, array<string, mixed>> $blocks Parsed blocks.
	 * @return array{id: int, review: bool, pros_cons: bool, price: string, currency: string}|null
	 */
	private static function first_schema_box( array $blocks ): ?array {
		foreach ( $blocks as $block ) {
			$attributes = is_array( $block['attrs'] ?? null ) ? $block['attrs'] : array();
			if ( self::BLOCK === ( $block['blockName'] ?? '' ) ) {
				if ( ( true === ( $attributes['schemaReview'] ?? false ) || true === ( $attributes['schemaProsCons'] ?? false ) ) && ! empty( $attributes['productId'] ) ) {
					return array(
						'id'        => is_numeric( $attributes['productId'] ) ? (int) $attributes['productId'] : 0,
						'review'    => true === ( $attributes['schemaReview'] ?? false ),
						'pros_cons' => true === ( $attributes['schemaProsCons'] ?? false ),
						'price'     => is_string( $attributes['schemaPrice'] ?? null ) ? $attributes['schemaPrice'] : '',
						'currency'  => is_string( $attributes['schemaPriceCurrency'] ?? null ) ? $attributes['schemaPriceCurrency'] : '',
					);
				}
				continue;
			}
			$found = self::first_schema_shortcode( is_string( $block['innerHTML'] ?? null ) ? $block['innerHTML'] : '' );
			$found = $found ?? ( is_array( $block['innerBlocks'] ?? null ) ? self::first_schema_box( $block['innerBlocks'] ) : null );
			if ( null !== $found ) {
				return $found;
			}
		}

		return null;
	}

	/**
	 * The first `[affibloom]` in the text with an `id` and `schema_review="enable"` or `schema_pros_cons="enable"`
	 * (R29); a shortcode quoted inside a block's attributes (`u0022`) is a copy, as Lasso skips it.
	 *
	 * @param string $html A block's HTML or a classic post's content.
	 * @return array{id: int, review: bool, pros_cons: bool, price: string, currency: string}|null
	 */
	private static function first_schema_shortcode( string $html ): ?array {
		if ( ! str_contains( $html, '[' ) || ! preg_match_all( '/' . get_shortcode_regex( self::SHORTCODES ) . '/s', $html, $matches, PREG_SET_ORDER ) ) {
			return null;
		}
		foreach ( $matches as $match ) {
			if ( str_contains( $match[3], 'u0022' ) ) {
				continue;
			}
			$attributes = ShortcodeAttributes::repaired( shortcode_parse_atts( $match[3] ) );
			$review     = 'enable' === ( $attributes['schema_review'] ?? '' );
			$pros_cons  = 'enable' === ( $attributes['schema_pros_cons'] ?? '' );
			if ( ! ctype_digit( $attributes['id'] ?? '' ) || ( ! $review && ! $pros_cons ) ) {
				continue;
			}

			return array(
				'id'        => (int) $attributes['id'],
				'review'    => $review,
				'pros_cons' => $pros_cons,
				'price'     => $attributes['schema_price'] ?? '',
				'currency'  => $attributes['schema_price_currency'] ?? '',
			);
		}

		return null;
	}
}
