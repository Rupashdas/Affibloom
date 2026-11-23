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
use Affibloom\Application\Groups\GroupService;
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
 * A block placed from Phase 4 on (it has a `type`: Tasks 23C-24A, F4 D3) draws as the shortcode draws it, with the
 * overrides it keeps under Lasso's names and a group found by the ID it keeps. A block saved before (no `type`, F5)
 * keeps its own path with its camelCase overrides. The editor's preview (EditorController) renders through this same
 * callback.
 */
final class ProductBoxBlock implements Module {

	/** The block's name. */
	public const NAME = 'affibloom/product-box';

	/** The shortcode that carries a drawn box past wptexturize (Task 29A). */
	public const RAW = 'affibloom_box_html';

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
	 * @param AffibloomShortcode      $shortcode The shortcode, which draws every display but the single box.
	 * @param GroupService            $groups    The groups, for a group display's slug.
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
		private readonly GroupService $groups,
	) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_block' ) );
	}

	/**
	 * The drawn box, back from its wrapper as it was drawn (Task 29A).
	 *
	 * @param mixed $attributes Unused.
	 * @param mixed $content    The box's HTML.
	 */
	public static function raw( mixed $attributes, mixed $content = '' ): string {
		unset( $attributes );

		return is_string( $content ) ? $content : '';
	}

	/**
	 * The wrapper's content is left as drawn by wptexturize, as a shortcode's output is.
	 *
	 * @param mixed $shortcodes The shortcodes wptexturize skips.
	 * @return list<string>|mixed
	 */
	public static function untextured( mixed $shortcodes ): mixed {
		return is_array( $shortcodes ) ? array_merge( $shortcodes, array( self::RAW ) ) : $shortcodes;
	}

	/**
	 * Registers the block type with its server-side render.
	 */
	public function register_block(): void {
		register_block_type( $this->block_dir, array( 'render_callback' => array( $this, 'render' ) ) );
		// What carries a drawn box past wptexturize (Task 29A).
		add_shortcode( self::RAW, array( self::class, 'raw' ) );
		add_filter( 'no_texturize_shortcodes', array( self::class, 'untextured' ) );
	}

	/**
	 * The box's HTML; '' for a product that is missing, a draft or retired without a published target (R1).
	 *
	 * @param array<string, mixed> $attributes The block's attributes.
	 */
	public function render( array $attributes ): string {
		return self::past_texturize( $this->draw( $attributes ) );
	}

	/**
	 * A drawn box as the content should carry it. Lasso's block drew its box through its shortcode, after WordPress's
	 * wptexturize (the_content 10), so its quotes and dashes stayed as typed; a block draws at do_blocks (9), before it.
	 * Inside the_content, with do_shortcode (11) still to come, the box goes through a shortcode wptexturize skips and
	 * do_shortcode unwraps (Task 29A); anywhere else (the editor's preview, REST) it is returned as drawn.
	 *
	 * @param string $html The drawn box.
	 */
	public static function past_texturize( string $html ): string {
		if ( '' === $html || ! doing_filter( 'the_content' ) || 11 > (int) has_filter( 'the_content', 'do_shortcode' ) ) {
			return $html;
		}

		return '[' . self::RAW . ']' . $html . '[/' . self::RAW . ']';
	}

	/**
	 * The box's HTML as drawn.
	 *
	 * @param array<string, mixed> $attributes The block's attributes.
	 */
	private function draw( array $attributes ): string {
		$group   = is_numeric( $attributes['groupId'] ?? null ) ? $this->groups->get( (int) $attributes['groupId'] ) : null;
		$request = DisplayRequest::from_block( $attributes, null === $group ? '' : $group->slug );
		if ( null !== $request && ( DisplayKind::Single !== $request->kind || '' !== ( $attributes['type'] ?? '' ) ) ) {
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
