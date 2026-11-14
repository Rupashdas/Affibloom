<?php
/**
 * One field's page, or "Add a New Field": Lasso's field page (F26, Tasks FL2-FL3).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Application\Fields\FieldsService;
use Affibloom\Domain\Fields\Field;
use Affibloom\Domain\Fields\FieldType;
use Affibloom\Interface\Template;

/**
 * One field's page, or "Add a New Field" when no field is named (`admin/views/fields/{header,field-details}.php`; F26
 * R12-R13). Reached from the Fields list, never from the menu. Task FL2 builds the new-field form; FL3 a field's Details
 * tab, with Delete This Field; FL4 adds its Links tab.
 */
final class FieldScreen {

	public const SLUG = 'affibloom-field';

	/**
	 * The field this request shows, found by guard() before any output; null for a new field.
	 *
	 * @var Field|null
	 */
	private ?Field $field = null;

	/**
	 * Builds the screen.
	 *
	 * @param Template      $template Template renderer.
	 * @param FieldsService $fields   The fields.
	 * @param Notice        $notice   The toast after a redirect: "Field saved." (F26 D19).
	 */
	public function __construct(
		private Template $template,
		private FieldsService $fields,
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
	 * Runs on the page's load hook, before WordPress prints anything: an ID that is not a field goes to the Fields list, as
	 * a missing group goes to the Groups list. No ID is the new-field form.
	 */
	public function guard(): void {
		$id = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- which page to show, no action behind it.
		if ( 0 === $id ) {
			return;
		}

		$this->field = $this->fields->get( $id );
		if ( null === $this->field ) {
			wp_safe_redirect( FieldsUrl::to( '', 1 ) );
			exit;
		}
	}

	/**
	 * Prints the page. WordPress has already checked the capability (Capabilities::PRODUCTS, in AdminMenu).
	 */
	public function render(): void {
		$this->template->render(
			'admin/field',
			array(
				'field'  => $this->field,
				'types'  => FieldType::choosable(),
				'usage'  => null === $this->field ? 0 : $this->fields->usage_of( $this->field->id ),
				'notice' => $this->notice->message(),
			)
		);
	}
}
