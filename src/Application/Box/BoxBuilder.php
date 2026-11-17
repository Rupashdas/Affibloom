<?php
/**
 * Turns a product, the Display settings and a box's overrides into what the box prints.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Box;

use Affibloom\Application\Display\DisplayPresentation;
use Affibloom\Domain\Amazon\AmazonProduct;
use Affibloom\Domain\Amazon\AmazonUrl;
use Affibloom\Domain\Amazon\PrimeRule;
use Affibloom\Domain\Amazon\ShownPrice;
use Affibloom\Domain\Amazon\TrackingIds;
use DateTimeImmutable;
use DateTimeZone;
use Affibloom\Domain\Box\BoxOverrides;
use Affibloom\Domain\Box\DescriptionRule;
use Affibloom\Domain\Box\DisclosureRule;
use Affibloom\Domain\Box\ElementVisibility;
use Affibloom\Domain\Box\LayoutFamily;
use Affibloom\Domain\Box\PriceRule;
use Affibloom\Domain\Box\ThemeResolver;
use Affibloom\Domain\Box\TimestampRule;
use Affibloom\Domain\Display\DisplaySettings;
use Affibloom\Domain\Display\DisplayType;
use Affibloom\Domain\Display\Elements;
use Affibloom\Domain\Fields\FieldLibrary;
use Affibloom\Domain\Fields\FieldType;
use Affibloom\Domain\Fields\ProductFields;
use Affibloom\Domain\Link\LinkAttributes;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Button;
use Affibloom\Domain\Product\Buttons;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Domain\Variant\VariantRepository;

/**
 * The box engine (F5 D1, D2): Lasso's Single rules, computed without side effects. Every box caller (the block, the
 * shortcodes, the previews) builds through it, with the settings it has; nothing here reads WordPress.
 *
 * Task 18A draws Lasso's advanced (v2) box, which every live box is; the classic branch is Task 18K. Further buttons
 * are 19A, the overrides 20A-20C, the stars and fields 21A-21C, and price, disclosure and date their own rules in 22A-22C.
 */
final class BoxBuilder {

	/**
	 * Which post a URL points to (WordPress's `url_to_postid`, 0 for none); null finds none.
	 *
	 * @var \Closure(string): int|null
	 */
	private readonly ?\Closure $post_of_url;

	/**
	 * The current moment, for the age of stored Amazon prices; null is now.
	 *
	 * @var \Closure(): DateTimeImmutable|null
	 */
	private readonly ?\Closure $clock;

	/**
	 * The product's public link on this site (F24 R13: its cloaked address when Link Cloaking is on); null is its
	 * destination.
	 *
	 * @var \Closure(Product): string|null
	 */
	private readonly ?\Closure $public_link;

	/**
	 * The products' URL variants, whose Amazon tags a box's tracking ID keeps (F27 R19; Task V4); null keeps none.
	 *
	 * @var VariantRepository|null
	 */
	private readonly ?VariantRepository $variants;

	/**
	 * Builds the engine.
	 *
	 * @param string                             $default_thumbnail Affibloom's own picture for a product without one (F5 D14).
	 * @param \Closure(string): int|null         $post_of_url       Which post a URL points to, for the self-link check (R40).
	 * @param \Closure(): DateTimeImmutable|null $clock     The current moment (tests); null is now.
	 * @param \Closure(Product): string|null     $public_link The product's public link (F24 R13; Task CL6); null is its destination.
	 * @param VariantRepository|null             $variants    The URL variants, whose Amazon tags are kept (F27 R19; Task V4).
	 */
	public function __construct( private readonly string $default_thumbnail, ?\Closure $post_of_url = null, ?\Closure $clock = null, ?\Closure $public_link = null, ?VariantRepository $variants = null ) {
		$this->post_of_url = $post_of_url;
		$this->clock       = $clock;
		$this->public_link = $public_link;
		$this->variants    = $variants;
	}

	/**
	 * The view of one Single box.
	 *
	 * @param Product            $product   A published product (the resolver has followed any repoint).
	 * @param DisplaySettings    $display   The Display settings, as DisplaySettingsService::current() gives them.
	 * @param Settings           $settings  The site's settings.
	 * @param BoxOverrides       $overrides The box's own values.
	 * @param int                $post_id   The post the box is drawn in (0 outside one), for the self-link check.
	 * @param BoxFields|null     $fields    The product's custom fields and the field library; null for none.
	 * @param AmazonProduct|null $amazon The Amazon product's stored data (C1); null when there is none.
	 * @param DisplayType|null   $type   The display type whose settings draw the box (a grid's cards: Grid, F30 R11,
	 *                                   R12, R18); null is Single.
	 */
	public function build( Product $product, DisplaySettings $display, Settings $settings, BoxOverrides $overrides, int $post_id = 0, ?BoxFields $fields = null, ?AmazonProduct $amazon = null, ?DisplayType $type = null ): BoxView {
		$config = $display->config( $type ?? DisplayType::single() );
		// R28 reads the Show Price saved on the product, not the box's `price`.
		$stored_show_price = $product->show_price;
		// C1 R48, N3: an Amazon product's price is the stored Amazon data's, when Amazon's API gave it within 24 hours;
		// the box's `price` replaces it after (Lasso's order).
		$amazon_discount = '';
		if ( ProductType::Amazon === $product->type ) {
			$shown           = ShownPrice::of( $amazon, null === $this->clock ? new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) ) : ( $this->clock )() );
			$product         = $product->with( array( 'price' => $shown->price ) );
			$amazon_discount = $shown->discount;
		}
		$product = self::applied( $product, $overrides, $settings, $this->variants );
		// R3, R65: the Single type's theme, then the product's own, then the box's.
		$theme = ThemeResolver::resolve( $config->theme(), $product->theme, $overrides->theme );
		$id    = (int) $product->id;

		// R68: the box's title replaces the name everywhere it shows (links, alt); `hide` empties it. R12: Lasso prints
		// the name through html_entity_decode; the template escapes it again.
		$name  = BoxOverrides::hides( $overrides->title ) ? '' : ( '' === $overrides->title ? $product->name : $overrides->title );
		$name  = html_entity_decode( $name, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$links = new LinkAttributes( $id, $name );
		// The public link (R40; F24 R13, Task CL6): the cloaked address of a cloaked non-Amazon product, else the
		// destination, Amazon's with its tag. A box's own primary URL is printed as given (Lasso's single.php, button.php).
		$href = '' === $overrides->primary_url && null !== $this->public_link ? ( $this->public_link )( $product ) : $product->url->value();
		$link = $links->primary( $href, $product->new_tab, $product->nofollow, $product->sponsored );
		// R76: the box's title URL takes the title and the image elsewhere, with the product's target and rel (R87).
		$title_link = '' === $overrides->title_url ? $link : array_merge( $link, array( 'href' => $overrides->title_url ) );
		// R53, R64: advanced rendering reads the Single element switches; the classic box shows every element by its own
		// data (only the box's own hides apply).
		$advanced = $display->advanced();
		$elements = $advanced ? ElementVisibility::of( $config->elements(), $overrides, $stored_show_price ) : ElementVisibility::of( Elements::defaults(), $overrides );

		$disclosure = DisclosureRule::text( $product->disclosure, $product->show_disclosure, $settings->string( 'disclosure_text' ) );
		// R78: the box's `prime` shows Prime whatever the product.
		$prime = $overrides->prime || PrimeRule::shows( $settings->bool( 'show_prime' ), ProductType::Amazon === $product->type, $product->asin, $product->prime );
		$price = PriceRule::prints( $product->price, $product->show_price, $settings->bool( 'show_price' ) ) ? $product->price : '';
		// R74, R21, C1 R51: the box's basis price, else Amazon's, struck through with the price while Show Discount Pricing
		// is on.
		$basis    = '' !== $overrides->basis_price ? $overrides->basis_price : $amazon_discount;
		$discount = '' !== $basis && '' !== $price && $settings->bool( 'show_discount_pricing' ) ? $basis : '';

		// R13: the SiteStripe card replaces the theme's layout while the site keeps the SiteStripe UI.
		$sitestripe = $overrides->sitestripe && $settings->bool( 'keep_site_stripe_ui' );

		return new BoxView(
			family: $sitestripe ? LayoutFamily::SiteStripe : LayoutFamily::of( $theme ),
			// R9, R81: the box's anchor ID, else Lasso's anchor with the box's link ID or, without one, uniqid(), so two boxes
			// of one product never share an ID.
			anchor: '' !== $overrides->anchor_id ? $overrides->anchor_id : 'affibloom-anchor-id-' . $id . '-' . ( '' !== $overrides->link_id ? $overrides->link_id : uniqid() ),
			// R6, R80: `mobile` or `desktop` (the box's style, or a text widget's box) after the slug.
			theme: 'affibloom-' . strtolower( $theme->name() ),
			classes: $sitestripe ? 'affibloom-display sitestripe affibloom-url-' . $product->slug : implode( ' ', array_filter( array( 'affibloom-display', 'affibloom-' . strtolower( $theme->name() ), 'affibloom-url-' . $product->slug, $overrides->style, $advanced ? 'affibloom-advanced' : '' ) ) ),
			// R64: the classic box has no v2 wrapper attributes; its colours come through the page's :root block.
			// R13: the card has only its size, Lasso's default 240 x 400 unless the box gives its own style.
			wrapper: $sitestripe ? array( 'style' => '' === $overrides->sitestripe_style ? 'width:240px;height:400px;' : $overrides->sitestripe_style ) : ( $advanced ? DisplayPresentation::wrapper( $config, $display->columns(), null !== $disclosure, $elements->hidden() ) : array() ),
			badge: BoxOverrides::hides( $overrides->badge ) ? '' : ( '' === $overrides->badge ? $product->badge : $overrides->badge ),
			badge_override: $advanced && $elements->badge_overrides(),
			link: $title_link,
			// R69: the box's picture and alternative text; a hidden picture keeps the product's address (Lasso prints
			// `src="hide"`, a broken request) and is hidden by the wrapper's flag.
			image_src: '' === $overrides->image_url || BoxOverrides::hides( $overrides->image_url ) ? $this->picture( $product, $settings ) : $overrides->image_url,
			image_alt: '' === $overrides->image_alt ? $name : $overrides->image_alt,
			// Edge case 9: without the wrapper's flag the classic box hides a hidden picture itself.
			image_hidden: ! $advanced && ! $elements->shows( 'image' ),
			// R61: a hidden title is not printed at all (the links keep the name).
			title: $elements->shows( 'title' ) ? $name : '',
			title_linked: $settings->bool( 'link_from_display_title' ),
			title_tag: $overrides->title_type,
			stars: self::stars( $fields, $overrides->rating ),
			price: $price,
			discount: $discount,
			price_block: '' !== $price || $prime,
			prime: $prime,
			// R63: the Description element gates the block itself.
			description: $elements->shows( 'desc' ) ? $this->description( $product, $overrides ) : null,
			// R63: fields are always printed; the Pros & cons element hides them by CSS.
			fields: self::with_box_fields( null === $fields ? array() : self::fields( $fields->values, $fields->library ), $overrides ),
			buttons: $this->buttons( $product, $links, $link, $config->palette()->value( 'primary_button_text' ), $config->palette()->value( 'secondary_button_text' ), $post_id ),
			disclosure: $disclosure,
			date: ( $advanced ? TimestampRule::shows( ProductType::Amazon === $product->type, $elements->shows( 'timestamp' ) ) : TimestampRule::shows_classic( ProductType::Amazon === $product->type, '' !== $price ) ) ? TimestampRule::text( $amazon->last_updated ?? '' ) : null,
			name: $name,
			slug: $product->slug,
		);
	}

	/**
	 * The product's buttons in its order (F29 D7, F6): the default button (the product's own link and switches) where
	 * it was put among the added ones; the first printed is `affibloom-button-1`, the others `affibloom-button-2` with
	 * their number. An added button needs a URL that is not the post the box is in (R40), takes the Single type's
	 * secondary label when its text is empty (R41) and Lasso's secondary `rel` (F6 AC3).
	 *
	 * @param Product               $product   The product.
	 * @param LinkAttributes        $links     The product's link attributes.
	 * @param array<string, string> $link      The default button's (primary) attributes.
	 * @param string                $primary   The Single type's primary label.
	 * @param string                $secondary The Single type's secondary label.
	 * @param int                   $post_id   The post the box is drawn in.
	 * @return list<BoxButton>
	 */
	private function buttons( Product $product, LinkAttributes $links, array $link, string $primary, string $secondary, int $post_id ): array {
		$rows  = array();
		$added = $product->buttons->all();
		foreach ( $added as $index => $button ) {
			if ( $index === $product->buttons->main_position() ) {
				$rows[] = null;
			}
			$rows[] = $button;
		}
		if ( count( $added ) <= $product->buttons->main_position() ) {
			$rows[] = null;
		}

		$out = array();
		foreach ( $rows as $button ) {
			$number = count( $out ) + 1;
			$class  = 1 === $number ? 'affibloom-button-1' : 'affibloom-button-2';
			if ( null === $button ) {
				$attributes = $link;
				if ( $number > 1 ) {
					$attributes = self::numbered( $attributes, $number );
				}
				$out[] = new BoxButton( $class, $attributes, '' === $product->button_text ? $primary : $product->button_text );
				continue;
			}

			$url = trim( $button->url );
			if ( '' === $url || $this->links_to_post( $url, $post_id ) ) {
				continue;
			}
			$text       = '' === $button->text ? $secondary : $button->text;
			$attributes = $links->secondary( $url, $text, $button->new_tab, $button->nofollow, $product->sponsored );
			$out[]      = new BoxButton( $class, 1 === $number ? $attributes : self::numbered( $attributes, $number ), $text );
		}

		return $out;
	}

	/**
	 * The product as this box sees it (F5 R73, R75, R82; Lasso's single.php 177-200 and Shortcode 434-440): the box's
	 * primary URL and text replace the product's link and button text; its secondary URL and text replace the first added
	 * button's (Lasso's second button), adding one when there is none; its disclosure text replaces the product's and
	 * shows it; its price hides, shows or replaces the price (R77); its tracking ID retags the Amazon link and that second
	 * button. `hide` stays the text, as Lasso prints
	 * it in classic rendering (edge case 10); advanced rendering hides the button (R60).
	 *
	 * @param Product                $product   The product.
	 * @param BoxOverrides           $overrides The box's values.
	 * @param Settings               $settings  The site's settings, for the tracking ID whitelist.
	 * @param VariantRepository|null $variants  The URL variants, whose Amazon tags are kept too (F27 R19).
	 */
	private static function applied( Product $product, BoxOverrides $overrides, Settings $settings, ?VariantRepository $variants ): Product {
		$changes = array();
		$url     = $product->url;
		$buttons = $product->buttons->all();

		// Lasso's shortcode retags the product's own links first; the box's URLs (single.php) replace them after. The box's
		// tag is kept only when the site's whitelist allows it (F31 A38, Lasso's get_amazon_product_url), else the site's.
		$amazon = AmazonUrl::from( $url );
		if ( '' !== $overrides->tracking_id && null !== $amazon ) {
			$tag    = TrackingIds::from_settings( $settings, $variants )->tag_for( $overrides->tracking_id );
			$url    = $amazon->retagged( $tag );
			$second = isset( $buttons[0] ) ? AmazonUrl::from( AffiliateUrl::from_string( $buttons[0]->url ) ) : null;
			if ( null !== $second ) {
				$buttons[0] = new Button( $second->retagged( $tag )->value(), $buttons[0]->text, $buttons[0]->new_tab, $buttons[0]->nofollow );
			}
		}

		if ( '' !== $overrides->primary_url ) {
			$url = AffiliateUrl::from_string( $overrides->primary_url );
		}
		if ( '' !== $overrides->primary_text ) {
			$changes['button_text'] = $overrides->primary_text;
		}
		if ( '' !== $overrides->secondary_url || '' !== $overrides->secondary_text ) {
			$second     = $buttons[0] ?? new Button( '', '' );
			$buttons[0] = new Button(
				'' === $overrides->secondary_url ? $second->url : $overrides->secondary_url,
				'' === $overrides->secondary_text ? $second->text : $overrides->secondary_text,
				$second->new_tab,
				$second->nofollow
			);
		}
		$changes['url']     = $url;
		$changes['buttons'] = new Buttons( $buttons, $product->buttons->main_position() );

		if ( '' !== $overrides->disclosure_text ) {
			$changes['disclosure']      = $overrides->disclosure_text;
			$changes['show_disclosure'] = true;
		}

		// R77: `hide` hides the price, `show` shows it, another value replaces it and shows it.
		if ( '' !== $overrides->price ) {
			$changes['show_price'] = ! BoxOverrides::hides( $overrides->price );
			if ( ! in_array( $overrides->price, array( BoxOverrides::HIDE, 'show' ), true ) ) {
				$changes['price'] = $overrides->price;
			}
		}

		return $product->with( $changes );
	}

	/**
	 * A button's attributes with its number before `rel`, where Lasso prints `data-lasso-button` (F12's
	 * `data-affibloom-btn`, deviation 132).
	 *
	 * @param array<string, string> $attributes The link's attributes, in Lasso's order.
	 * @param int                   $number     The button's place in the box, from 1.
	 * @return array<string, string>
	 */
	private static function numbered( array $attributes, int $number ): array {
		$rel = $attributes['rel'];
		unset( $attributes['rel'] );
		$attributes['data-affibloom-btn'] = (string) $number;
		$attributes['rel']                = $rel;

		return $attributes;
	}

	/**
	 * Lasso's self-link check (single.php 226-229): the URL is the post the box is drawn in.
	 *
	 * @param string $url     The button's URL.
	 * @param int    $post_id The post the box is drawn in; 0 outside one, where nothing matches.
	 */
	private function links_to_post( string $url, int $post_id ): bool {
		return $post_id > 0 && null !== $this->post_of_url && ( $this->post_of_url )( $url ) === $post_id;
	}

	/**
	 * The Primary Rating's stars (R14, R70; F8 AC1-AC4): its row when visible and holding a value; the name when the row
	 * shows it. The box's `rating` replaces the value (adding stars, without a name, when the product has none) or, set
	 * to `hide`, leaves none. A value that is not a number draws no stars (Lasso prints it raw into `--rating`).
	 *
	 * @param BoxFields|null $fields The product's field rows and the library; null for none.
	 * @param string         $box    The box's rating: '', `hide` or a value.
	 */
	private static function stars( ?BoxFields $fields, string $box ): ?BoxStars {
		if ( BoxOverrides::hides( $box ) ) {
			return null;
		}

		$row = null;
		foreach ( null === $fields ? array() : $fields->values->all() as $candidate ) {
			if ( ProductFields::RATING_ID === $candidate->field_id ) {
				$row = $candidate;
				break;
			}
		}
		if ( '' === $box && ( null === $row || ! $row->visible ) ) {
			return null;
		}

		$rating = trim( '' === $box ? $row->value : $box );
		if ( ! is_numeric( $rating ) ) {
			return null;
		}
		$label = null !== $fields && null !== $row && $row->show_name ? (string) $fields->library->find( ProductFields::RATING_ID )?->name : '';

		return new BoxStars( $rating, number_format( (float) $rating, 1, '.', '' ), $label );
	}

	/**
	 * The fields block (R23, R30-R35): the product's visible rows in their order, without the Primary Rating (the stars,
	 * R14) and the table-only fields, each printed by its type.
	 *
	 * @param ProductFields $values  The product's field rows.
	 * @param FieldLibrary  $library The fields, for names and types.
	 * @return list<BoxField>
	 */
	private static function fields( ProductFields $values, FieldLibrary $library ): array {
		$out = array();
		foreach ( $values->all() as $row ) {
			$field = $library->find( $row->field_id );
			if ( ! $row->visible || ProductFields::RATING_ID === $row->field_id || null === $field || $field->table_only ) {
				continue;
			}
			$kind = match ( true ) {
				in_array( $row->field_id, array( 2, 3 ), true ) => BoxField::PROS_CONS,
				FieldType::Rating === $field->type             => BoxField::RATING,
				FieldType::BulletedList === $field->type       => BoxField::BULLETED,
				FieldType::NumberedList === $field->type       => BoxField::NUMBERED,
				FieldType::CopyClipboard === $field->type      => BoxField::COPY,
				default                                        => BoxField::TEXT,
			};
			$out[] = new BoxField( $kind, $row->field_id, $field->name, $row->value );
		}

		return $out;
	}

	/**
	 * The fields as the box changes them (R71, R72; Lasso's single.php 93-175): its `pros` / `cons` replace the field's
	 * lines (and its name, "Pros" / "Cons" unless a label is given) or add it last; a label alone renames it; `hide`
	 * leaves no fields; `demo` shows Lasso's demo Pros & Cons while `show_pros_cons` is true, else none.
	 *
	 * @param list<BoxField> $fields    The product's fields block.
	 * @param BoxOverrides   $overrides The box's values.
	 * @return list<BoxField>
	 */
	private static function with_box_fields( array $fields, BoxOverrides $overrides ): array {
		if ( BoxOverrides::hides( $overrides->fields ) ) {
			return array();
		}
		if ( 'demo' === $overrides->fields && null !== $overrides->show_pros_cons ) {
			return $overrides->show_pros_cons ? array(
				new BoxField( BoxField::PROS_CONS, 2, 'Pros', "Will make you more effective.\nQuick read and highly actionable." ),
				new BoxField( BoxField::PROS_CONS, 3, 'Cons', 'You have to be open minded.' ),
			) : array();
		}

		foreach ( array(
			2 => array( $overrides->pros, $overrides->pros_label, 'Pros' ),
			3 => array( $overrides->cons, $overrides->cons_label, 'Cons' ),
		) as $id => list( $lines, $label, $default ) ) {
			if ( '' === $lines && '' === $label ) {
				continue;
			}
			$found = false;
			foreach ( $fields as $index => $field ) {
				if ( $id === $field->id ) {
					$found            = true;
					$fields[ $index ] = new BoxField( BoxField::PROS_CONS, $id, '' === $label ? $default : $label, '' === $lines ? $field->value : $lines );
				}
			}
			if ( ! $found && '' !== $lines ) {
				$fields[] = new BoxField( BoxField::PROS_CONS, $id, '' === $label ? $default : $label, $lines );
			}
		}

		return $fields;
	}

	/**
	 * The description block's text (R67): `hide` prints none; the box's own text replaces the product's and forces the
	 * block on; else Lasso's rule for the product's.
	 *
	 * @param Product      $product   The product.
	 * @param BoxOverrides $overrides The box's values.
	 */
	private function description( Product $product, BoxOverrides $overrides ): ?string {
		if ( BoxOverrides::hides( $overrides->description ) ) {
			return null;
		}
		if ( '' !== $overrides->description ) {
			return DescriptionRule::text( $overrides->description );
		}

		return DescriptionRule::shows( $product->show_description, $product->description ) ? DescriptionRule::text( $product->description ) : null;
	}

	/**
	 * The picture (R17, the parts that exist yet): the product's image, else the site's default thumbnail, else
	 * Affibloom's own. The structured data reads it too (SchemaMarkup, Task 20D).
	 *
	 * @param Product  $product  The product.
	 * @param Settings $settings The site's settings.
	 */
	public function picture( Product $product, Settings $settings ): string {
		if ( '' !== $product->image_url ) {
			return $product->image_url;
		}

		$site = $settings->string( 'default_thumbnail' );

		return '' === $site ? $this->default_thumbnail : $site;
	}
}
