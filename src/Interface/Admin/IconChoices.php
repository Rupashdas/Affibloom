<?php
/**
 * The button icons as the admin screens show them (F28).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Application\Icons\IconService;
use Affibloom\Domain\Icons\Icon;
use Affibloom\Domain\Icons\IconText;

/**
 * The button icons as the admin screens show them: each with a picture the admin can load (the "Button icons" card,
 * Task 14F), and the view of an icon text field (the picker, Task 14G). Extracted from DisplayScreen when the product
 * page became the second screen to need it.
 *
 * @phpstan-type IconRow array{icon: Icon, src: string}
 * @phpstan-type IconField array{id: string, name: string, label: string, value: string, placeholder: string, help_id: string, text: IconText|null, words_placeholder: string, current: IconRow|null, unknown: string, rows: list<IconRow>}
 */
final class IconChoices {

	/**
	 * The rows, built once per request.
	 *
	 * @var list<IconRow>|null
	 */
	private ?array $rows = null;

	/**
	 * Builds the choices.
	 *
	 * @param IconService $icons The icon set.
	 */
	public function __construct( private IconService $icons ) {}

	/**
	 * Every icon with a link the admin can show. A media library item gives its own; otherwise the link the site's CSS
	 * writes, made absolute (the site writes "/wp-content/…" and, once, "wp-content/…", which inside wp-admin would
	 * point into /wp-admin/).
	 *
	 * @return list<IconRow>
	 */
	public function rows(): array {
		if ( null === $this->rows ) {
			$this->rows = array();
			foreach ( $this->icons->all()->all() as $icon ) {
				$src          = $icon->attachment > 0 ? (string) wp_get_attachment_url( $icon->attachment ) : '';
				$src          = '' !== $src ? $src : ( 1 === preg_match( '#^https?://#i', $icon->url ) ? $icon->url : home_url( '/' . ltrim( $icon->url, '/' ) ) );
				$this->rows[] = array(
					'icon' => $icon,
					'src'  => $src,
				);
			}
		}

		return $this->rows;
	}

	/**
	 * The view of one icon text field (templates/admin/partials/icon-text-field.php). A value that is not an icon and
	 * words (other HTML) has no text: the field stays a plain text field (F28 requirements B5). A class the set does
	 * not have is "unknown" and is kept until another icon is chosen (D2).
	 *
	 * @param string $id          The stored field's id.
	 * @param string $name        Its form name.
	 * @param string $label       Its label, for the picker's name.
	 * @param string $value       The stored text.
	 * @param string $placeholder The text an empty field falls back to ('' for none).
	 * @param string $help_id     The help text's id, for aria-describedby.
	 * @return IconField
	 */
	public function field( string $id, string $name, string $label, string $value, string $placeholder, string $help_id ): array {
		$text    = IconText::split( $value );
		$hint    = IconText::split( $placeholder );
		$current = null;
		$unknown = '';
		if ( null !== $text && '' !== $text->css_class ) {
			foreach ( $this->rows() as $row ) {
				if ( $row['icon']->css_class === $text->css_class ) {
					$current = $row;
				}
			}
			$unknown = null === $current ? $text->css_class : '';
		}

		return array(
			'id'                => $id,
			'name'              => $name,
			'label'             => $label,
			'value'             => $value,
			'placeholder'       => $placeholder,
			'help_id'           => $help_id,
			'text'              => $text,
			'words_placeholder' => null !== $hint ? $hint->words : $placeholder,
			'current'           => $current,
			'unknown'           => $unknown,
			'rows'              => $this->rows(),
		);
	}
}
