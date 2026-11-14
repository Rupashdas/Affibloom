<?php
/**
 * The Fields list: Lasso's Fields page (F26, Task FL1).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Application\Fields\FieldsService;
use Affibloom\Interface\Template;

/**
 * The Fields list: Lasso's Fields page (`admin/views/fields/fields.php`; F26 R7-R11). A hidden page, as Lasso's is: it is
 * reached from "Manage Fields" in the product page's Add Field dialog, never from the menu. The page prints the first
 * list; search and page links redraw it in place through GET /fields/list, which renders the same partial.
 */
final class FieldsScreen {

	public const SLUG = 'affibloom-fields';

	/**
	 * Builds the screen.
	 *
	 * @param Template      $template Template renderer.
	 * @param FieldsService $fields   The fields.
	 * @param Notice        $notice   The toast after a redirect: "Field saved." and "Successfully deleted the Field." (Tasks FL2, FL3).
	 */
	public function __construct(
		private Template $template,
		private FieldsService $fields,
		private Notice $notice,
	) {}

	/**
	 * Prints the page. WordPress has already checked the capability (Capabilities::PRODUCTS, in AdminMenu).
	 */
	public function render(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- a read-only list, with no action behind it.
		$page = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;
		$term = isset( $_GET['s'] ) ? trim( sanitize_text_field( wp_unslash( $_GET['s'] ) ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$this->template->render(
			'admin/fields',
			array(
				'listing' => $this->fields->list( $term, $page ),
				'term'    => $term,
				'url'     => FieldsUrl::to( $term, 1 ),
				'notice'  => $this->notice->message(),
			)
		);
	}
}
