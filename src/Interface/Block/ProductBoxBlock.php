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
use Affibloom\Domain\Box\BoxOverrides;
use Affibloom\Domain\Fields\FieldRepository;
use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Domain\Settings\SettingsRepository;
use Affibloom\Interface\Front\BoxHtml;
use Affibloom\Module;

/**
 * Registers `affibloom/product-box` from `blocks/product-box/block.json` and renders it on the server (F5 R1, AC3).
 * The editor side (inserter, sidebar, preview) is Phase 4.
 */
final class ProductBoxBlock implements Module {

	/**
	 * Builds the block.
	 *
	 * @param string                 $block_dir The folder that holds block.json.
	 * @param RepointResolver        $products  Which product a box draws.
	 * @param DisplaySettingsService $display   The Display settings.
	 * @param SettingsRepository     $settings  The site's settings.
	 * @param BoxBuilder             $builder   The box engine.
	 * @param BoxHtml                $html      The box's printer.
	 * @param ProductRepository      $catalog   The products, for their custom fields.
	 * @param FieldRepository        $fields    The field library.
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
		$overrides = BoxOverrides::from_block( $attributes );
		$product   = $this->products->product( $overrides->product_id );
		if ( null === $product ) {
			return '';
		}

		$values = $this->catalog->fields( (int) $product->id );
		$fields = null === $values ? null : new BoxFields( $values, $this->fields->load() );

		return $this->html->render( $this->builder->build( $product, $this->display->current(), $this->settings->get(), $overrides, (int) get_the_ID(), $fields ) );
	}
}
