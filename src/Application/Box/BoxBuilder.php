<?php
/**
 * Turns a product, the Display settings and a box's overrides into what the box prints.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Box;

use Affibloom\Application\Display\DisplayPresentation;
use Affibloom\Domain\Box\BoxOverrides;
use Affibloom\Domain\Box\DescriptionRule;
use Affibloom\Domain\Box\LayoutFamily;
use Affibloom\Domain\Display\DisplaySettings;
use Affibloom\Domain\Display\DisplayType;
use Affibloom\Domain\Link\LinkAttributes;
use Affibloom\Domain\Product\Product;
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
	 * Builds the engine.
	 *
	 * @param string $default_thumbnail Affibloom's own picture for a product without one (F5 D14).
	 */
	public function __construct( private readonly string $default_thumbnail ) {}

	/**
	 * The view of one Single box.
	 *
	 * @param Product         $product   A published product (the resolver has followed any repoint).
	 * @param DisplaySettings $display   The Display settings, as DisplaySettingsService::current() gives them.
	 * @param Settings        $settings  The site's settings.
	 * @param BoxOverrides    $overrides The box's own values.
	 */
	public function build( Product $product, DisplaySettings $display, Settings $settings, BoxOverrides $overrides ): BoxView { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- the overrides arrive with Tasks 20A-20C.
		$config = $display->config( DisplayType::single() );
		// R3, its first two steps: the Single type's theme, then the product's own (the box's own is Task 18E).
		$theme = $product->theme ?? $config->theme();
		$id    = (int) $product->id;

		// R12: Lasso prints the name through html_entity_decode; the template escapes it again.
		$name  = html_entity_decode( $product->name, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$links = new LinkAttributes( $id, $name );
		// The public link (R40) is the destination: Amazon's already holds its tag, and the cloaked permalink of other
		// products comes with the links area.
		$link = $links->primary( $product->url->value(), $product->new_tab, $product->nofollow, $product->sponsored );

		$disclosure = $this->disclosure( $product, $settings );
		$prime      = $settings->bool( 'show_prime' ) && $product->prime;
		$price      = ( $product->show_price ?? $settings->bool( 'show_price' ) ) ? $product->price : '';
		$label      = '' === $product->button_text ? $config->palette()->value( 'primary_button_text' ) : $product->button_text;

		return new BoxView(
			family: LayoutFamily::of( $theme ),
			// R9 without a link location: Lasso's uniqid(), so two boxes of one product never share an ID.
			anchor: 'affibloom-anchor-id-' . $id . '-' . uniqid(),
			classes: implode( ' ', array( 'affibloom-display', 'affibloom-' . strtolower( $theme->name() ), 'affibloom-url-' . $product->slug, 'affibloom-advanced' ) ),
			wrapper: DisplayPresentation::wrapper( $config, $display->columns(), null !== $disclosure ),
			badge: $product->badge,
			link: $link,
			image_src: $this->image( $product, $settings ),
			image_alt: $name,
			title: $name,
			title_linked: $settings->bool( 'link_from_display_title' ),
			price: $price,
			price_block: '' !== $price || $prime,
			prime: $prime,
			description: DescriptionRule::shows( $product->show_description, $product->description ) ? DescriptionRule::text( $product->description ) : null,
			buttons: array( new BoxButton( 'affibloom-button-1', $link, $label ) ),
			disclosure: $disclosure,
		);
	}

	/**
	 * The disclosure text when the box shows it (R24, R25): the product's own, trimmed, else the site's; shown when
	 * the product's Show Disclosure is on and there is text.
	 *
	 * @param Product  $product  The product.
	 * @param Settings $settings The site's settings.
	 */
	private function disclosure( Product $product, Settings $settings ): ?string {
		$text = trim( $product->disclosure );
		if ( '' === $text ) {
			$text = $settings->string( 'disclosure_text' );
		}

		return $product->show_disclosure && '' !== $text ? $text : null;
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
