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
use Affibloom\Domain\Amazon\AmazonProduct;
use Affibloom\Domain\Amazon\AmazonProductRepository;
use Affibloom\Domain\Amazon\AmazonUrl;
use Affibloom\Domain\Amazon\ShownPrice;
use Affibloom\Domain\Box\BoxOverrides;
use Affibloom\Domain\Box\LayoutFamily;
use Affibloom\Domain\Fields\FieldRepository;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Domain\Settings\SettingsRepository;
use Affibloom\Domain\Shortcode\AnchorId;
use Affibloom\Domain\Shortcode\DisplayKind;
use Affibloom\Domain\Shortcode\DisplayRequest;
use Affibloom\Domain\Shortcode\FieldPart;
use DateTimeImmutable;
use DateTimeZone;

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
	 * One inline value's data (F31 R11; Lasso's aawp/fields.php): the part and its plain value, or for `thumb` its picture
	 * options. Lasso's Amazon rating and review count are always 0 (C1 R53).
	 *
	 * @param DisplayRequest                     $request The request.
	 * @param Product                            $product The product.
	 * @param AmazonProduct|null                 $stored  Its stored Amazon data.
	 * @param \Affibloom\Application\Box\BoxView $view The product's box view (name, picture, links, timestamp).
	 * @return array<string, string>
	 */
	private static function inline( DisplayRequest $request, Product $product, ?AmazonProduct $stored, \Affibloom\Application\Box\BoxView $view ): array {
		$part = FieldPart::tryFrom( strtolower( trim( $request->attributes['value'] ?? '' ) ) );
		if ( null === $part ) {
			return array();
		}
		$fresh   = null !== $stored && '' !== ShownPrice::of( $stored, new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) ) )->price;
		$price   = ProductType::Amazon === $product->type ? ShownPrice::of( $stored, new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) ) )->price : $product->price;
		$amazon  = AmazonUrl::from( $product->url );
		$reviews = null === $amazon || '' === $amazon->asin() ? '' : 'https://www.' . preg_replace( '/^www\./', '', $product->url->host() ) . '/product-reviews/' . $amazon->asin();
		$name    = html_entity_decode( BoxOverrides::hides( $request->overrides->title ) ? '' : ( '' === $request->overrides->title ? $product->name : $request->overrides->title ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$value   = match ( $part ) {
			FieldPart::Title           => $name,
			FieldPart::Image           => $view->image_src,
			FieldPart::Price           => $price,
			FieldPart::ListPrice       => $fresh ? $stored->savings_basis : '',
			FieldPart::AmountSaved     => $fresh ? $stored->savings_amount : '',
			FieldPart::PercentageSaved => $fresh ? (string) $stored->savings_percent : '',
			FieldPart::Rating          => '0',
			FieldPart::Reviews         => '0 Reviews',
			FieldPart::Url             => $view->buttons[0]->attributes['href'] ?? '',
			FieldPart::Description     => null === $view->description ? '' : $view->description,
			FieldPart::LastUpdate      => '' !== $price && ( $product->show_price ?? false ) && null !== $view->date ? $view->date : '',
			default                    => '',
		};

		return array(
			'part'         => $part->value,
			'value'        => $value,
			'name'         => $name,
			'reviews'      => $reviews,
			'image_size'   => strtolower( $request->attributes['image_size'] ?? 'medium' ),
			'image_class'  => $request->attributes['image_class'] ?? '',
			'image_align'  => strtolower( $request->attributes['image_align'] ?? '' ),
			'image_width'  => $request->attributes['image_width'] ?? '',
			'image_height' => $request->attributes['image_height'] ?? '',
		);
	}

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

		// A10: a lone button, a lone image, a text link (A40) or one inline value (R11).
		return match ( $request->kind ) {
			DisplayKind::Button => $view->as_family( LayoutFamily::Button ),
			DisplayKind::Image  => $view->as_family( LayoutFamily::Image ),
			DisplayKind::Link   => $view->as_family( LayoutFamily::Link )->with_inline( array( 'text' => '' !== trim( $request->attributes['label'] ?? '' ) ? trim( $request->attributes['label'] ) : html_entity_decode( '' === $overrides->title ? $product->name : $overrides->title, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) ),
			DisplayKind::Field  => $view->as_family( LayoutFamily::Field )->with_inline( self::inline( $request, $product, $stored, $view ) ),
			default             => $view,
		};
	}
}
