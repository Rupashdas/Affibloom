<?php
/**
 * The Product box block on the front end: `<!-- wp:affibloom/product-box {"productId":N} /-->` prints the product's box.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Block;

use Affibloom\Application\Box\BoxBuilder;
use Affibloom\Application\Box\BoxFields;
use Affibloom\Application\Box\RepointResolver;
use Affibloom\Application\Display\DisplaySettingsService;
use Affibloom\Domain\Amazon\AmazonProductRepository;
use Affibloom\Domain\Box\BoxOverrides;
use Affibloom\Domain\Fields\FieldRepository;
use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Domain\Settings\SettingsRepository;
use Affibloom\Domain\Shortcode\DisplayKind;
use Affibloom\Domain\Shortcode\DisplayRequest;
use Affibloom\Interface\Front\AffibloomShortcode;
use Affibloom\Interface\Front\BoxHtml;
use Affibloom\Module;

/**
 * Registers `affibloom/product-box` from the built `build/product-box/block.json` (its editor script and style, Task
 * 23A; the source is `blocks/product-box`) and renders it on the server (F5 R1, AC3).
 * A Button or an Image display (Task 23C, F4 D3) draws as the shortcode draws it; the single box keeps its own path,
 * with the block's overrides. The editor's preview (EditorController) renders through this same callback.
 */
final class ProductBoxBlock implements Module {

	/** The block's name. */
	public const NAME = 'affibloom/product-box';

	/**
	 * Builds the block.
	 *
	 * @param string                  $block_dir The folder that holds block.json.
	 * @param RepointResolver         $products  Which product a box draws.
	 * @param DisplaySettingsService  $display   The Display settings.
	 * @param SettingsRepository      $settings  The site's settings.
	 * @param BoxBuilder              $builder   The box engine.
	 * @param BoxHtml                 $html      The box's printer.
	 * @param ProductRepository       $catalog   The products, for their custom fields.
	 * @param FieldRepository         $fields    The field library.
	 * @param AmazonProductRepository $amazon   The stored Amazon data (C1), for the timestamp.
	 * @param AffibloomShortcode      $shortcode The shortcode, which draws the Button and Image displays.
	 */
	public function __construct(
		private readonly string $block_dir,
		private readonly RepointResolver $products,
		private readonly DisplaySettingsService $display,
		private readonly SettingsRepository $settings,
		private readonly BoxBuilder $builder,
		private readonly BoxHtml $html,
		private readonly ProductRepository $catalog,
		private readonly FieldRepository $fields,
		private readonly AmazonProductRepository $amazon,
		private readonly AffibloomShortcode $shortcode,
	) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_block' ) );
	}

	/**
	 * Registers the block type with its server-side render.
	 */
	public function register_block(): void {
		register_block_type( $this->block_dir, array( 'render_callback' => array( $this, 'render' ) ) );
	}

	/**
	 * The box's HTML; '' for a product that is missing, a draft or retired without a published target (R1).
	 *
	 * @param array<string, mixed> $attributes The block's attributes.
	 */
	public function render( array $attributes ): string {
		$request = DisplayRequest::from_block( $attributes );
		if ( null !== $request && DisplayKind::Single !== $request->kind ) {
			return $this->shortcode->render( $request->attributes );
		}

		$overrides = BoxOverrides::from_block( $attributes );
		$product   = $this->products->product( $overrides->product_id );
		if ( null === $product ) {
			return '';
		}

		$values = $this->catalog->fields( (int) $product->id );
		$fields = null === $values ? null : new BoxFields( $values, $this->fields->load() );

		$amazon = ProductType::Amazon === $product->type && '' !== $product->asin ? $this->amazon->find( $product->asin, $product->amazon_country ) : null;

		return $this->html->render( $this->builder->build( $product, $this->display->current(), $this->settings->get(), $overrides, (int) get_the_ID(), $fields, $amazon ) );
	}
}
