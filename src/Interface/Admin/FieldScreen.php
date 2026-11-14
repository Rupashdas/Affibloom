<?php
/**
 * "Add a New Field": Lasso's field page without a field (F26, Task FL2).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Domain\Fields\FieldType;
use Affibloom\Interface\Template;

/**
 * One field's page, or "Add a New Field" when no field is named (`admin/views/fields/{header,field-details}.php`; F26
 * R12-R13). Reached from the Fields list, never from the menu. Task FL2 builds the new-field form; FL3 adds a field's
 * Details tab, with Delete This Field, and FL4 its Links tab.
 */
final class FieldScreen {

	public const SLUG = 'affibloom-field';

	/**
	 * Builds the screen.
	 *
	 * @param Template $template Template renderer.
	 * @param Notice   $notice   The toast after a redirect: "Field saved." (F26 D19).
	 */
	public function __construct(
		private Template $template,
		private Notice $notice,
	) {}

	/**
	 * The admin address of a field's page, or of "Add a New Field" when no ID is given.
	 *
	 * @param int    $id  The field's ID; 0 for a new field.
	 * @param string $tab The tab to open ('links' or 'details'); '' for the page's first tab.
	 */
	public static function url( int $id = 0, string $tab = '' ): string {
		$args = array( 'page' => self::SLUG );
		if ( $id > 0 ) {
			$args['id'] = $id;
		}
		if ( '' !== $tab ) {
			$args['tab'] = $tab;
		}

		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}

	/**
	 * Prints the page. WordPress has already checked the capability (Capabilities::PRODUCTS, in AdminMenu).
	 */
	public function render(): void {
		$this->template->render(
			'admin/field',
			array(
				'types'  => FieldType::choosable(),
				'notice' => $this->notice->message(),
			)
		);
	}
}
