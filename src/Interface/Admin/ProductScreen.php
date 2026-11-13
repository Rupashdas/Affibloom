<?php
/**
 * The product page: Lasso's link details page (url-details), Details tab.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Application\Display\DisplaySettingsService;
use Affibloom\Application\Products\ProductService;
use Affibloom\Domain\Display\DisplayType;
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
	 */
	public function __construct(
		private Template $template,
		private ProductService $products,
		private DisplaySettingsService $display,
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
	 * Prints the page. WordPress has already checked the capability (Capabilities::PRODUCTS, in AdminMenu) and guard()
	 * has found the product.
	 */
	public function render(): void {
		if ( null === $this->product ) {
			return;
		}

		$palette = $this->display->config( DisplayType::single() )->palette();
		$this->template->render(
			'admin/product',
			array(
				'product'      => $this->product,
				// Lasso's placeholders are the site's button texts (get_lasso_url: *_button_text_default).
				'button_text'  => $palette->value( 'primary_button_text' ),
				'button_text2' => $palette->value( 'secondary_button_text' ),
				// Lasso's "add" redirect for a link the site already has (url-details.php 2597-2600).
				'duplicate'    => isset( $_GET['is_duplicate'] ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
			)
		);
	}
}
