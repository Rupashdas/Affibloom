<?php
/**
 * The site's button icons, in the order the editor sees them (F28).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Icons;

use Affibloom\Domain\InvalidField;

/**
 * The site's button icons, in the order the editor sees them (F28 requirements B1-B3). Immutable: add() and remove()
 * return a new set.
 */
final class IconSet {

	/**
	 * The icons, keyed by nothing: classes are unique within a set.
	 *
	 * @var list<Icon>
	 */
	private array $icons = array();

	/**
	 * Builds a set; a class that is already in it keeps its first icon.
	 *
	 * @param list<Icon> $icons The icons, in order.
	 */
	public function __construct( array $icons ) {
		foreach ( $icons as $icon ) {
			if ( null === $this->find( $icon->css_class ) ) {
				$this->icons[] = $icon;
			}
		}
	}

	/**
	 * A set from storage. A row that is not a valid icon is skipped, so a damaged option never breaks the screens.
	 *
	 * @param array<mixed> $stored Rows as to_array() writes them.
	 */
	public static function from_array( array $stored ): self {
		$icons = array();
		foreach ( $stored as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			try {
				$icons[] = new Icon(
					is_string( $row['class'] ?? null ) ? $row['class'] : '',
					is_string( $row['name'] ?? null ) ? $row['name'] : '',
					is_string( $row['url'] ?? null ) ? $row['url'] : '',
					is_int( $row['attachment'] ?? null ) ? $row['attachment'] : 0,
					true === ( $row['imported'] ?? false ),
				);
			} catch ( InvalidField ) {
				continue;
			}
		}

		return new self( $icons );
	}

	/**
	 * The set as stored.
	 *
	 * @return list<array{class: string, name: string, url: string, attachment: int, imported: bool}>
	 */
	public function to_array(): array {
		return array_map(
			static fn( Icon $icon ): array => array(
				'class'      => $icon->css_class,
				'name'       => $icon->name,
				'url'        => $icon->url,
				'attachment' => $icon->attachment,
				'imported'   => $icon->imported,
			),
			$this->icons
		);
	}

	/**
	 * Every icon, in order.
	 *
	 * @return list<Icon>
	 */
	public function all(): array {
		return $this->icons;
	}

	/**
	 * The icon with this class, or null.
	 *
	 * @param string $css_class A class such as "medal-icon".
	 */
	public function find( string $css_class ): ?Icon {
		foreach ( $this->icons as $icon ) {
			if ( $icon->css_class === $css_class ) {
				return $icon;
			}
		}
		return null;
	}

	/**
	 * A set with a new icon at its end. Its class is the name as a slug plus "-icon" ("Under Armour" →
	 * "under-armour-icon"), numbered when taken ("medal-2-icon"), "custom-icon" when the name has no letter or digit.
	 *
	 * @param string $name       1-40 characters.
	 * @param string $url        The image.
	 * @param int    $attachment The media library item.
	 * @throws InvalidField When the name or the link breaks its rule.
	 */
	public function add( string $name, string $url, int $attachment ): self {
		$name = trim( $name );
		$slug = trim( (string) preg_replace( '/[^a-z0-9]+/', '-', strtolower( $name ) ), '-' );
		$slug = '' === $slug ? 'custom' : $slug;

		$css_class = $slug . '-icon';
		$number    = 1;
		while ( null !== $this->find( $css_class ) ) {
			++$number;
			$css_class = $slug . '-' . $number . '-icon';
		}

		return new self( array_merge( $this->icons, array( new Icon( $css_class, $name, $url, $attachment, false ) ) ) );
	}

	/**
	 * A set without an icon Affibloom added. An imported icon is the site's CSS, so it cannot be removed here.
	 *
	 * @param string $css_class The icon's class.
	 * @throws InvalidField When the icon is imported or not in the set.
	 */
	public function remove( string $css_class ): self {
		$icon = $this->find( $css_class );
		if ( null === $icon ) {
			throw new InvalidField( 'class', 'not_found' );
		}
		if ( $icon->imported ) {
			throw new InvalidField( 'class', 'imported' );
		}

		return new self( array_values( array_filter( $this->icons, static fn( Icon $other ): bool => $other !== $icon ) ) );
	}
}
