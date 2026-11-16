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
use Affibloom\Module;
use WP_Post;

/**
 * `wp_head` (F5 R90, AC17; Lasso's lasso_schema_markup_output): the first Product box block in the post's content with
 * `schemaReview` or `schemaProsCons` on prints one `<script type="application/ld+json">`, once per post. Lasso reads the
 * product's stored name, picture, price, Primary Rating and Pros / Cons, not the box's overrides; `schemaPrice` and
 * `schemaPriceCurrency` replace the price and currency. Shortcode boxes join through the same printer (Task 18S14).
 */
final class SchemaMarkup implements Module {

	/**
	 * The block's name.
	 */
	private const BLOCK = 'affibloom/product-box';

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

		$attributes = self::first_schema_box( parse_blocks( $post->post_content ) );
		if ( null === $attributes ) {
			return;
		}
		$product = $this->products->product( is_numeric( $attributes['productId'] ?? null ) ? (int) $attributes['productId'] : 0 );
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
		$currency = is_string( $attributes['schemaPriceCurrency'] ?? null ) && '' !== $attributes['schemaPriceCurrency'] ? $attributes['schemaPriceCurrency'] : ( $stored->currency ?? 'USD' );
		$price    = is_string( $attributes['schemaPrice'] ?? null ) && '' !== $attributes['schemaPrice'] ? $attributes['schemaPrice'] : $product->price;

		$schema = SchemaBuilder::build(
			$product->name,
			$this->builder->picture( $product, $this->settings->get() ),
			$price,
			$currency,
			$rating,
			$lines( 2 ),
			$lines( 3 ),
			(string) get_the_author_meta( 'display_name', (int) $post->post_author ),
			true === ( $attributes['schemaReview'] ?? false ),
			true === ( $attributes['schemaProsCons'] ?? false )
		);

		echo PHP_EOL . '<script type="application/ld+json" class="affibloom-schema-markup-output">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ) . '</script>' . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON, its "<" escaped (JSON_HEX_TAG).
	}

	/**
	 * The attributes of the first Product box block, nested blocks included, that enables Review or Pro/Con schema;
	 * Lasso stops at the first box that asks, whether or not its product exists.
	 *
	 * @param array<int, array<string, mixed>> $blocks Parsed blocks.
	 * @return array<string, mixed>|null
	 */
	private static function first_schema_box( array $blocks ): ?array {
		foreach ( $blocks as $block ) {
			$attributes = is_array( $block['attrs'] ?? null ) ? $block['attrs'] : array();
			if ( self::BLOCK === ( $block['blockName'] ?? '' ) && ( true === ( $attributes['schemaReview'] ?? false ) || true === ( $attributes['schemaProsCons'] ?? false ) ) && ! empty( $attributes['productId'] ) ) {
				return $attributes;
			}
			$inner = is_array( $block['innerBlocks'] ?? null ) ? self::first_schema_box( $block['innerBlocks'] ) : null;
			if ( null !== $inner ) {
				return $inner;
			}
		}

		return null;
	}
}
