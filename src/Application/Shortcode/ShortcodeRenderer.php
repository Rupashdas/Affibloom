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
use Affibloom\Application\Box\GridView;
use Affibloom\Application\Box\ListView;
use Affibloom\Application\Box\RepointResolver;
use Affibloom\Application\Display\DisplayPresentation;
use Affibloom\Application\Display\DisplaySettingsService;
use Affibloom\Application\Groups\GroupService;
use Affibloom\Domain\Amazon\AmazonProduct;
use Affibloom\Domain\Amazon\AmazonProductRepository;
use Affibloom\Domain\Amazon\AmazonUrl;
use Affibloom\Domain\Amazon\ShownPrice;
use Affibloom\Domain\Box\BoxOverrides;
use Affibloom\Domain\Box\ElementVisibility;
use Affibloom\Domain\Box\LayoutFamily;
use Affibloom\Domain\Display\DisplayType;
use Affibloom\Domain\Display\GridColumns;
use Affibloom\Domain\Display\GridDisclosure;
use Affibloom\Domain\Display\ListBullets;
use Affibloom\Domain\Display\Theme;
use Affibloom\Domain\Fields\FieldRepository;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Domain\Settings\SettingsRepository;
use Affibloom\Domain\Shortcode\AnchorId;
use Affibloom\Domain\Shortcode\DisplayKind;
use Affibloom\Domain\Shortcode\DisplayRequest;
use Affibloom\Domain\Shortcode\FieldPart;
use Affibloom\Domain\Shortcode\GroupAttributes;
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
	 * @param GroupService            $groups   The groups, for a group display's products.
	 */
	public function __construct(
		private readonly ProductRepository $catalog,
		private readonly RepointResolver $products,
		private readonly FieldRepository $fields,
		private readonly AmazonProductRepository $amazon,
		private readonly DisplaySettingsService $display,
		private readonly SettingsRepository $settings,
		private readonly BoxBuilder $builder,
		private readonly GroupService $groups,
	) {}

	/**
	 * A group shown as a grid (F30 R13-R19, R55, R61; D5, D16, D17; N1): the group's shown products as Single boxes in
	 * the Grid settings (compact ones without description and fields), in rows of `columns` with classic settings, each with the shortcode's per-box values but not its `tracking_id` (Lasso reloads a group's
	 * products, R5) or `anchor_id`; the wrapper with the Grid settings (customised), then the grid's disclosure. An
	 * unknown or empty group is the wrapper alone (and the disclosure when it shows).
	 *
	 * @param DisplayRequest $request The shortcode's request.
	 * @param int            $number  The display's number among the page's `[affibloom]` displays, from 1.
	 * @param int            $post_id The post it is in (0 outside one).
	 */
	public function grid( DisplayRequest $request, int $number, int $post_id ): GridView {
		$group    = GroupAttributes::of( $request->attributes );
		$display  = $this->display->current();
		$settings = $this->settings->get();
		$advanced = $display->advanced();
		$config   = $display->config( DisplayType::grid() );
		$per_card = BoxOverrides::from_shortcode( array_diff_key( $request->attributes, array_flip( array( 'tracking_id', 'anchor_id' ) ) ) );

		$cards = array();
		foreach ( $this->groups->shown( $group->group, $group->limit, $group->latest ) as $product ) {
			$card    = $this->built( $product, $per_card, $post_id, DisplayType::grid() )->as_card();
			$cards[] = $group->compact ? $card->compact() : $card;
		}
		// R14, N1: the shortcode's columns in both modes; customised settings draw them through the wrapper's count.
		$columns = GridColumns::of( $group->columns, $advanced, $display->columns() );

		$shows = GridDisclosure::shows( $advanced, $group->disclosure, $settings->bool( 'show_disclosure_grid' ) );
		$text  = $settings->string( 'disclosure_text' );
		// R16: the wrapper carries the Grid settings without the price flag; each card has its own (R18).
		$hidden = array_values( array_diff( ElementVisibility::of( $config->elements(), $per_card )->hidden(), array( 'price' ) ) );

		return new GridView(
			anchor: AnchorId::of( $request->overrides->anchor_id, $group->group, $number ),
			classes: 'affibloom-grid-wrap' . ( $advanced ? ' affibloom-advanced' : '' ),
			wrapper: $advanced ? DisplayPresentation::wrapper( $config, $columns, $shows, $hidden ) : array(),
			cards: $cards,
			disclosure: $shows && '' !== trim( $text ) ? $text : null,
			per_row: $advanced ? null : $columns,
		);
	}

	/**
	 * A group shown as an image gallery (F30 R25, R26; D3, D9): the group's shown products as lone images (the
	 * gallery's `image_url` on each; the alt text the product's name, as Lasso's), in rows of `columns` (2-5, else 4),
	 * inside the grid's wrapper without settings, theme or disclosure; only the wrapper has an id.
	 *
	 * @param DisplayRequest $request The shortcode's request.
	 * @param int            $number  The display's number among the page's `[affibloom]` displays, from 1.
	 * @param int            $post_id The post it is in (0 outside one).
	 */
	public function gallery( DisplayRequest $request, int $number, int $post_id ): GridView {
		$group    = GroupAttributes::of( $request->attributes );
		$per_card = BoxOverrides::from_shortcode( array_diff_key( $request->attributes, array_flip( array( 'tracking_id', 'anchor_id', 'image_alt' ) ) ) );

		$items = array();
		foreach ( $this->groups->shown( $group->group, $group->limit, $group->latest ) as $product ) {
			$items[] = $this->built( $product, $per_card, $post_id, DisplayType::single() )->as_family( LayoutFamily::Image )->with_anchor( '' );
		}

		return new GridView(
			anchor: AnchorId::of( $request->overrides->anchor_id, $group->group, $number ),
			classes: 'affibloom-grid-wrap',
			wrapper: array(),
			cards: $items,
			disclosure: null,
			per_row: GridColumns::gallery( $group->columns ),
		);
	}

	/**
	 * A group shown as a list (F30 R21-R23; D6, D7): the group's shown products as list cards in the List settings, each
	 * with the shortcode's per-box values (not its `tracking_id` or `anchor_id`) and its own disclosure; the list's
	 * bullets and the List settings' attributes without the price and disclosure flags.
	 *
	 * @param DisplayRequest $request The shortcode's request.
	 * @param int            $number  The display's number among the page's `[affibloom]` displays, from 1.
	 * @param int            $post_id The post it is in (0 outside one).
	 */
	public function list( DisplayRequest $request, int $number, int $post_id ): ListView {
		$group    = GroupAttributes::of( $request->attributes );
		$display  = $this->display->current();
		$advanced = $display->advanced();
		$config   = $display->config( DisplayType::list() );
		$per_card = BoxOverrides::from_shortcode( array_diff_key( $request->attributes, array_flip( array( 'tracking_id', 'anchor_id' ) ) ) );

		$cards = array();
		foreach ( $this->groups->shown( $group->group, $group->limit, $group->latest ) as $product ) {
			$cards[] = $this->built( $product, $per_card, $post_id, DisplayType::list() );
		}
		$bullets = ListBullets::of( $group->bullets );
		$hidden  = array_values( array_diff( ElementVisibility::of( $config->elements(), $per_card )->hidden(), array( 'price' ) ) );

		return new ListView(
			anchor: AnchorId::of( $request->overrides->anchor_id, $group->group, $number ),
			tag: $bullets->tag,
			classes: $bullets->classes . ( $advanced ? ' affibloom-advanced' : '' ),
			// R21, R23: the list shows each card's disclosure, so the list carries no disclosure flag.
			wrapper: $advanced ? DisplayPresentation::wrapper( $config, $display->columns(), true, $hidden ) : array(),
			cards: $cards,
		);
	}

	/**
	 * One product's box in a display type's settings.
	 *
	 * @param Product      $product   The product.
	 * @param BoxOverrides $overrides The box's own values.
	 * @param int          $post_id   The post it is in.
	 * @param DisplayType  $type      Single, or Grid for a grid's card.
	 */
	private function built( Product $product, BoxOverrides $overrides, int $post_id, DisplayType $type ): BoxView {
		$values = $this->catalog->fields( (int) $product->id );

		return $this->builder->build(
			$product,
			$this->display->current(),
			$this->settings->get(),
			$overrides,
			$post_id,
			null === $values ? null : new BoxFields( $values, $this->fields->load() ),
			$this->stored( $product ),
			$type
		);
	}

	/**
	 * An Amazon product's stored data, or null.
	 *
	 * @param Product $product The product.
	 */
	private function stored( Product $product ): ?AmazonProduct {
		return ProductType::Amazon === $product->type && '' !== $product->asin ? $this->amazon->find( $product->asin, $product->amazon_country ) : null;
	}

	/**
	 * The published product an `amazon_url` (its ASIN and store) or a `geni_url` (its destination) names, or null (A6, A7).
	 *
	 * @param string $amazon_url  An Amazon link; '' none.
	 * @param string $destination A destination; '' none.
	 */
	private function by_link( string $amazon_url, string $destination ): ?Product {
		if ( '' !== $amazon_url ) {
			try {
				$amazon = AmazonUrl::from( AffiliateUrl::from_string( $amazon_url ) );
			} catch ( InvalidField ) {
				$amazon = null;
			}
			if ( null !== $amazon && '' !== $amazon->asin() ) {
				$found = $this->catalog->find_amazon( $amazon->asin(), $amazon->country() );
				if ( null !== $found ) {
					return $found;
				}
			}
		}
		if ( '' !== $destination ) {
			try {
				return $this->catalog->find_by_url_key( AffiliateUrl::from_string( $destination )->key() );
			} catch ( InvalidField ) {
				return null;
			}
		}

		return null;
	}

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
		$id      = '' === $request->address->slug ? null : $this->catalog->id_by_slug( $request->address->slug );
		$id      = $id ?? $request->address->id;
		$product = $id > 0 ? $this->products->product( $id ) : null;
		$product = $product ?? $this->by_link( $request->address->amazon_url, $request->address->destination );
		if ( null === $product ) {
			return null;
		}

		$stored    = $this->stored( $product );
		$overrides = $request->overrides;
		$view      = $this->built( $product, $overrides, $post_id, DisplayType::single() );

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

		// F30 D10: Lasso's button takes the box's theme name unchecked (`lasso-<name>`, which nothing styles); a name
		// outside the 8 prints no theme class, the same look.
		if ( DisplayKind::Button === $request->kind && '' !== trim( $overrides->theme ) && ! Theme::known( $overrides->theme ) ) {
			$view = $view->without_theme();
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
