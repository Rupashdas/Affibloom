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
 * @phpstan-type IconRow array{icon: Icon, src: string, missing: bool}
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
	 * point into /wp-admin/). "Missing" is an image whose file is not on the site, so the screens show a warning
	 * instead of a broken picture (F28 D4).
	 *
	 * @return list<IconRow>
	 */
	public function rows(): array {
		if ( null === $this->rows ) {
			$this->rows = array();
			$uploads    = wp_get_upload_dir();
			foreach ( $this->icons->all()->all() as $icon ) {
				$src          = $icon->attachment > 0 ? (string) wp_get_attachment_url( $icon->attachment ) : '';
				$src          = '' !== $src ? $src : ( 1 === preg_match( '#^https?://#i', $icon->url ) ? $icon->url : home_url( '/' . ltrim( $icon->url, '/' ) ) );
				$file         = $icon->attachment > 0 ? (string) get_attached_file( $icon->attachment ) : '';
				$file         = '' !== $file ? $file : self::upload_file( $src, $uploads['baseurl'], $uploads['basedir'] );
				$this->rows[] = array(
					'icon'    => $icon,
					'src'     => $src,
					'missing' => '' !== $file && ! file_exists( $file ),
				);
			}
		}

		return $this->rows;
	}

	/**
	 * The file in the uploads folder a link points to, or '' when it points elsewhere (another host, a theme): then
	 * Affibloom cannot tell whether it is there and does not flag it. The scheme is ignored (the admin may run on https
	 * while the uploads URL says http); a query or fragment is dropped.
	 *
	 * @param string $src     The image's absolute link.
	 * @param string $baseurl The uploads folder's URL.
	 * @param string $basedir The uploads folder's path.
	 */
	public static function upload_file( string $src, string $baseurl, string $basedir ): string {
		$link = (string) preg_replace( array( '/[?#].*$/s', '#^https?:#i' ), '', $src );
		$base = rtrim( (string) preg_replace( '#^https?:#i', '', $baseurl ), '/' ) . '/';
		if ( ! str_starts_with( $link, $base ) ) {
			return '';
		}

		return rtrim( $basedir, '/\\' ) . '/' . rawurldecode( substr( $link, strlen( $base ) ) );
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
