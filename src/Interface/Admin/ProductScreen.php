<?php
/**
 * The product page: Lasso's link details page (url-details), Details tab.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Application\Display\DisplaySettingsService;
use Affibloom\Application\Fields\FieldsService;
use Affibloom\Application\Products\ProductService;
use Affibloom\Application\Settings\SettingsService;
use Affibloom\Domain\Amazon\AmazonUrl;
use Affibloom\Domain\Display\DisplayType;
use Affibloom\Domain\Display\Theme;
use Affibloom\Domain\Fields\FieldType;
use Affibloom\Domain\Fields\ProductFields;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Domain\Product\Product;
use Affibloom\Interface\Template;

/**
 * The product page: Lasso's link details page (url-details), Details tab (F1 requirements, sections 3-4). Reached
 * from the Dashboard row and after "Add A New Link", never from the menu.
 */
final class ProductScreen {

	public const SLUG = 'affibloom-product';

	/**
	 * The product this request shows, found by guard() before any output.
	 *
	 * @var Product|null
	 */
	private ?Product $product = null;

	/**
	 * Builds the screen.
	 *
	 * @param Template               $template Template renderer.
	 * @param ProductService         $products The product library.
	 * @param DisplaySettingsService $display  The Display settings: the button texts an empty field falls back to.
	 * @param IconChoices            $icons    The button icons, for the button text pickers (F28, Task 14G).
	 * @param SettingsService        $settings The site's settings: the disclosure text and Show Price a product falls
	 *                                         back to (Task 14B).
	 * @param FieldsService          $fields   The product's custom fields and the field library (Task 14C).
	 * @param Notice                 $notice   The toast after a redirect, e.g. "Tracking ID Saved!" (Task 14D).
	 */
	public function __construct(
		private Template $template,
		private ProductService $products,
		private DisplaySettingsService $display,
		private IconChoices $icons,
		private SettingsService $settings,
		private FieldsService $fields,
		private Notice $notice,
	) {}

	/**
	 * The admin address of a product's page.
	 *
	 * @param int $id Product ID.
	 */
	public static function url( int $id ): string {
		return add_query_arg(
			array(
				'page' => self::SLUG,
				'id'   => $id,
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Runs on the page's load hook, before WordPress prints anything: an ID that is not a published product goes to
	 * the Dashboard, as Lasso sends another post type's ID there (url-details.php 71-74). A draft or trashed product is
	 * treated the same, since Lasso fills its page only for a published record (F1 requirements, edge case 1).
	 */
	public function guard(): void {
		$id            = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- which page to show, no action behind it.
		$this->product = $this->products->get( $id );
		if ( null === $this->product ) {
			wp_safe_redirect( admin_url( 'admin.php?page=' . AdminMenu::SLUG ) );
			exit;
		}
	}

	/**
	 * Lasso's amazon-url-detected notice (Task 14D): on an Amazon product's page when the site has no tracking ID, the
	 * link's tag to offer ('' when it has none, and the notice links to Settings → Amazon instead); null otherwise.
	 *
	 * @param string $site_tag The site's tracking ID.
	 * @return array{tag: string, product: int, settings: string}|null
	 */
	private function tracking_offer( string $site_tag ): ?array {
		if ( null === $this->product || ProductType::Amazon !== $this->product->type || '' !== $site_tag ) {
			return null;
		}
		$amazon = AmazonUrl::from( $this->product->url );

		return array(
			'tag'      => null === $amazon ? '' : $amazon->tag(),
			'product'  => (int) $this->product->id,
			'settings' => admin_url( 'admin.php?page=' . SettingsScreen::SLUG . '&tab=amazon' ),
		);
	}

	/**
	 * Prints the page. WordPress has already checked the capability (Capabilities::PRODUCTS, in AdminMenu) and guard()
	 * has found the product.
	 */
	public function render(): void {
		if ( null === $this->product ) {
			return;
		}

		// The description and price editors keep Lasso's toolbars, whatever other plugins do to editors (Task 14B).
		ProductEditors::apply();

		$single     = $this->display->config( DisplayType::single() );
		$palette    = $single->palette();
		$site       = $this->settings->current();
		$disclosure = $site->string( 'disclosure_text' );
		$this->template->render(
			'admin/product',
			array(
				'product'      => $this->product,
				// Lasso's placeholders are the site's button texts (get_lasso_url: *_button_text_default).
				'button_text'  => $palette->value( 'primary_button_text' ),
				'button_text2' => $palette->value( 'secondary_button_text' ),
				// Lasso's "add" redirect for a link the site already has (url-details.php 2597-2600).
				'duplicate'    => isset( $_GET['is_duplicate'] ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
				'choices'      => $this->icons,
				// The right card (Task 14B). "Default (<theme>)" names the Single type's theme, as Lasso's select does.
				'theme'        => $single->theme()->name(),
				'themes'       => Theme::NAMES,
				'disclosure'   => $disclosure,
				// Lasso shows a stored disclosure equal to the site's text as empty (header.php 210-212), so a later
				// change of the site's text reaches it once saved (F1 requirements, edge case 3).
				'own_text'     => $this->product->disclosure === $disclosure ? '' : $this->product->disclosure,
				'show_price'   => $this->product->show_price ?? $site->bool( 'show_price' ),
				'amazon_tab'   => admin_url( 'admin.php?page=' . SettingsScreen::SLUG . '&tab=amazon' ),
				// Custom fields (Task 14C): the product's rows, every definition, and the kinds the dialog offers.
				'fields'       => $this->fields->fields( (int) $this->product->id ) ?? ProductFields::from_array( array() ),
				'library'      => $this->fields->all(),
				'types'        => FieldType::cases(),
				'tracking'     => $this->tracking_offer( $site->string( 'tracking_id' ) ),
				'notice'       => $this->notice->message(),
			)
		);
	}
}
