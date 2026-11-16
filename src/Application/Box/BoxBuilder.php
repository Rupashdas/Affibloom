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
use Affibloom\Domain\Fields\FieldLibrary;
use Affibloom\Domain\Fields\ProductFields;
use Affibloom\Domain\Link\LinkAttributes;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Domain\Settings\Settings;

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
	 * Builds the engine.
	 *
	 * @param string                     $default_thumbnail Affibloom's own picture for a product without one (F5 D14).
	 * @param \Closure(string): int|null $post_of_url       Which post a URL points to, for the self-link check (R40).
	 */
	public function __construct( private readonly string $default_thumbnail, ?\Closure $post_of_url = null ) {
		$this->post_of_url = $post_of_url;
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
	 */
	public function build( Product $product, DisplaySettings $display, Settings $settings, BoxOverrides $overrides, int $post_id = 0, ?BoxFields $fields = null, ?AmazonProduct $amazon = null ): BoxView {
		$config = $display->config( DisplayType::single() );
		// R3: the Single type's theme, then the product's own; a box names none until its `theme` attribute (Task 20B).
		$theme = ThemeResolver::resolve( $config->theme(), $product->theme, '' );
		$id    = (int) $product->id;

		// R68: the box's title replaces the name everywhere it shows (links, alt); `hide` empties it. R12: Lasso prints
		// the name through html_entity_decode; the template escapes it again.
		$name  = BoxOverrides::hides( $overrides->title ) ? '' : ( '' === $overrides->title ? $product->name : $overrides->title );
		$name  = html_entity_decode( $name, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$links = new LinkAttributes( $id, $name );
		// The public link (R40) is the destination: Amazon's already holds its tag, and the cloaked permalink of other
		// products comes with the links area.
		$link = $links->primary( $product->url->value(), $product->new_tab, $product->nofollow, $product->sponsored );
		// R76: the box's title URL takes the title and the image elsewhere, with the product's target and rel (R87).
		$title_link = '' === $overrides->title_url ? $link : array_merge( $link, array( 'href' => $overrides->title_url ) );
		$elements   = ElementVisibility::of( $config->elements(), $overrides, $product->show_price );

		$disclosure = DisclosureRule::text( $product->disclosure, $product->show_disclosure, $settings->string( 'disclosure_text' ) );
		$prime      = PriceRule::prime( ProductType::Amazon === $product->type, $settings->bool( 'show_prime' ), $product->prime );
		$price      = PriceRule::prints( $product->price, $product->show_price, $settings->bool( 'show_price' ) ) ? $product->price : '';

		return new BoxView(
			family: LayoutFamily::of( $theme ),
			// R9 without a link location: Lasso's uniqid(), so two boxes of one product never share an ID.
			anchor: 'affibloom-anchor-id-' . $id . '-' . uniqid(),
			classes: implode( ' ', array( 'affibloom-display', 'affibloom-' . strtolower( $theme->name() ), 'affibloom-url-' . $product->slug, 'affibloom-advanced' ) ),
			wrapper: DisplayPresentation::wrapper( $config, $display->columns(), null !== $disclosure, $elements->hidden() ),
			badge: BoxOverrides::hides( $overrides->badge ) ? '' : ( '' === $overrides->badge ? $product->badge : $overrides->badge ),
			badge_override: $elements->badge_overrides(),
			link: $title_link,
			// R69: the box's picture and alternative text; a hidden picture keeps the product's address (Lasso prints
			// `src="hide"`, a broken request) and is hidden by the wrapper's flag.
			image_src: '' === $overrides->image_url || BoxOverrides::hides( $overrides->image_url ) ? $this->image( $product, $settings ) : $overrides->image_url,
			image_alt: '' === $overrides->image_alt ? $name : $overrides->image_alt,
			// R61: a hidden title is not printed at all (the links keep the name).
			title: $elements->shows( 'title' ) ? $name : '',
			title_linked: $settings->bool( 'link_from_display_title' ),
			title_tag: $overrides->title_type,
			stars: null === $fields ? null : self::stars( $fields->values, $fields->library ),
			price: $price,
			price_block: '' !== $price || $prime,
			prime: $prime,
			// R63: the Description element gates the block itself.
			description: $elements->shows( 'desc' ) ? $this->description( $product, $overrides ) : null,
			buttons: $this->buttons( $product, $links, $link, $config->palette()->value( 'primary_button_text' ), $config->palette()->value( 'secondary_button_text' ), $post_id ),
			disclosure: $disclosure,
			date: TimestampRule::shows( ProductType::Amazon === $product->type, $elements->shows( 'timestamp' ) ) ? TimestampRule::text( $amazon->last_updated ?? '' ) : null,
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
	 * The Primary Rating's stars (R14; F8 AC1-AC4): its row when visible and holding a value; the name when the row
	 * shows it. A value that is not a number draws no stars (Lasso prints it raw into `--rating`).
	 *
	 * @param ProductFields $values  The product's field rows.
	 * @param FieldLibrary  $library The fields, for the name.
	 */
	private static function stars( ProductFields $values, FieldLibrary $library ): ?BoxStars {
		foreach ( $values->all() as $row ) {
			if ( ProductFields::RATING_ID !== $row->field_id ) {
				continue;
			}
			$rating = trim( $row->value );
			if ( ! $row->visible || ! is_numeric( $rating ) ) {
				return null;
			}

			return new BoxStars(
				$rating,
				number_format( (float) $rating, 1, '.', '' ),
				$row->show_name ? (string) $library->find( ProductFields::RATING_ID )?->name : ''
			);
		}

		return null;
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
	 * Affibloom's own.
	 *
	 * @param Product  $product  The product.
	 * @param Settings $settings The site's settings.
	 */
	private function image( Product $product, Settings $settings ): string {
		if ( '' !== $product->image_url ) {
			return $product->image_url;
		}

		$site = $settings->string( 'default_thumbnail' );

		return '' === $site ? $this->default_thumbnail : $site;
	}
}
