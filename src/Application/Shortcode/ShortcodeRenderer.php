<?php
/**
 * Draws what an `[affibloom]` shortcode asks for.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Shortcode;

use Affibloom\Application\Box\BoxBuilder;
use Affibloom\Application\Box\BoxFields;
use Affibloom\Application\Box\BoxView;
use Affibloom\Application\Box\RepointResolver;
use Affibloom\Application\Display\DisplaySettingsService;
use Affibloom\Domain\Amazon\AmazonProductRepository;
use Affibloom\Domain\Box\LayoutFamily;
use Affibloom\Domain\Fields\FieldRepository;
use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Domain\Settings\SettingsRepository;
use Affibloom\Domain\Shortcode\AnchorId;
use Affibloom\Domain\Shortcode\DisplayKind;
use Affibloom\Domain\Shortcode\DisplayRequest;

/**
 * The single box of a request (F31 R8, R14, A1, A2, A5, A31, A39): the slug's product, else the ID's, through the repoint
 * walk; nothing for a missing, draft, trashed or deleted product (R14). The view goes to BoxHtml like the block's.
 */
final class ShortcodeRenderer {

	/**
	 * Builds the renderer.
	 *
	 * @param ProductRepository       $catalog  The products.
	 * @param RepointResolver         $products The repoint walk.
	 * @param FieldRepository         $fields   The field library.
	 * @param AmazonProductRepository $amazon   The stored Amazon data.
	 * @param DisplaySettingsService  $display  The Display settings.
	 * @param SettingsRepository      $settings The site's settings.
	 * @param BoxBuilder              $builder  The box engine.
	 */
	public function __construct(
		private readonly ProductRepository $catalog,
		private readonly RepointResolver $products,
		private readonly FieldRepository $fields,
		private readonly AmazonProductRepository $amazon,
		private readonly DisplaySettingsService $display,
		private readonly SettingsRepository $settings,
		private readonly BoxBuilder $builder,
	) {}

	/**
	 * The box's view, or null when no product draws (R14).
	 *
	 * @param DisplayRequest $request The shortcode's request.
	 * @param int            $number  The box's number among the page's `[affibloom]` boxes, from 1.
	 * @param int            $post_id The post it is in (0 outside one).
	 */
	public function view( DisplayRequest $request, int $number, int $post_id ): ?BoxView {
		$id = '' === $request->address->slug ? null : $this->catalog->id_by_slug( $request->address->slug );
		$id = $id ?? $request->address->id;
		if ( $id <= 0 ) {
			return null;
		}
		$product = $this->products->product( $id );
		if ( null === $product ) {
			return null;
		}

		$values    = $this->catalog->fields( (int) $product->id );
		$stored    = ProductType::Amazon === $product->type && '' !== $product->asin ? $this->amazon->find( $product->asin, $product->amazon_country ) : null;
		$overrides = $request->overrides;
		$view      = $this->builder->build(
			$product,
			$this->display->current(),
			$this->settings->get(),
			$overrides,
			$post_id,
			null === $values ? null : new BoxFields( $values, $this->fields->load() ),
			$stored
		);

		// D5: a deterministic anchor (the block keeps Lasso's uniqid()).
		$view = $view->with_anchor( AnchorId::of( $overrides->anchor_id, (int) $product->id, $number ) );

		// A34: button_type="secondary" draws the second button when the product has one.
		if ( DisplayKind::Button === $request->kind && $request->secondary ) {
			foreach ( $view->buttons as $button ) {
				if ( 'affibloom-button-2' === $button->css_class ) {
					$view = $view->with_buttons( array( $button ) );
					break;
				}
			}
		}

		// A10: a lone button or a lone image.
		return match ( $request->kind ) {
			DisplayKind::Button => $view->as_family( LayoutFamily::Button ),
			DisplayKind::Image  => $view->as_family( LayoutFamily::Image ),
			default             => $view,
		};
	}
}
