<?php
/**
 * A block's display as `[affibloom]` text, and back.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Display;

use Affibloom\Domain\Shortcode\DisplayKind;

/**
 * The block's shortcode box (F4 R7, R41, R49; D4; Task 24C): a display written as the shortcode that draws the same,
 * in Lasso's order (the target first: `id`, `type`, `group`; then the overrides in the catalogue's order), and a typed
 * shortcode's attributes read back as a display. What the shortcode feature reads, this writes (F31).
 */
final class ShortcodeFormat {

	/** The types that show a group. */
	private const GROUP_TYPES = array( 'grid', 'list', 'gallery' );

	/** The names read() takes as the display's target. */
	private const TARGET = array( 'id', 'type', 'group', 'category', 'categories' );

	/** The schema panel's attributes (F4 R46; Task 24B): shortcode name => the block's attribute. */
	private const SCHEMA = array(
		'schema_review'         => 'schemaReview',
		'schema_pros_cons'      => 'schemaProsCons',
		'schema_price'          => 'schemaPrice',
		'schema_price_currency' => 'schemaPriceCurrency',
	);

	/** A toggle's values that a block keeps, or that mean its default (OverrideCatalog::overrides()). */
	private const TOGGLE_VALUES = array( '', 'hide', 'show', 'enable', 'disable' );

	/**
	 * The display as shortcode text; '' when it names nothing.
	 *
	 * @param string               $type       The block's type ('' or `single` the single box).
	 * @param int                  $product    Its product's ID, for the product types.
	 * @param string               $group_slug Its group's slug, for the group types.
	 * @param array<string, mixed> $overrides  Its overrides, under Lasso's names.
	 * @param int                  $table      Its table's ID, for the table type (F25, Task 23F).
	 */
	public static function format( string $type, int $product, string $group_slug, array $overrides, int $table = 0 ): string {
		$type  = '' === $type ? 'single' : $type;
		$parts = array();
		// A comparison table (F4 R24, Task 23F): `[affibloom type="table" id="<table>"]`, no overrides.
		if ( 'table' === $type ) {
			return $table > 0 ? '[affibloom type="table" id="' . $table . '"]' : '';
		}
		if ( in_array( $type, self::GROUP_TYPES, true ) ) {
			if ( '' === $group_slug ) {
				return '';
			}
			$parts = array(
				'type'  => $type,
				'group' => $group_slug,
			);
		} elseif ( $product > 0 ) {
			$parts = array( 'id' => (string) $product );
			if ( 'single' !== $type ) {
				$parts['type'] = $type;
			}
		} else {
			return '';
		}

		$text = '';
		foreach ( $parts + OverrideCatalog::overrides( $type, $overrides ) as $name => $value ) {
			$text .= ' ' . $name . '="' . $value . '"';
		}

		return '[affibloom' . $text . ']';
	}

	/**
	 * A typed shortcode's attributes (repaired, ShortcodeAttributes) as a display: its type, its product's ID or its group's
	 * slug, and its overrides. Null for one that names no product or group, or a kind the block does not draw.
	 *
	 * @param array<string, string> $attributes The repaired attributes.
	 * @return array{type: string, product: int, group: string, table: int, overrides: array<string, string>}|null
	 */
	public static function read( array $attributes ): ?array {
		$kind = DisplayKind::of( $attributes );
		// A comparison table (Task 23F): its ID.
		if ( DisplayKind::Table === $kind ) {
			$table = ctype_digit( trim( $attributes['id'] ?? '' ) ) ? (int) trim( $attributes['id'] ) : 0;
			return 0 === $table ? null : array(
				'type'      => 'table',
				'product'   => 0,
				'group'     => '',
				'table'     => $table,
				'overrides' => array(),
			);
		}
		$type = match ( $kind ) {
			DisplayKind::Single  => 'single',
			DisplayKind::Button  => 'button',
			DisplayKind::Image   => 'image',
			DisplayKind::Grid    => 'grid',
			DisplayKind::List    => 'list',
			DisplayKind::Gallery => 'gallery',
			default              => '',
		};
		if ( '' === $type ) {
			return null;
		}

		$group   = in_array( $type, self::GROUP_TYPES, true ) ? trim( $attributes['group'] ?? $attributes['categories'] ?? $attributes['category'] ?? '' ) : '';
		$product = '' === $group && ctype_digit( trim( $attributes['id'] ?? '' ) ) ? (int) trim( $attributes['id'] ) : 0;
		if ( 0 === $product && '' === $group ) {
			return null;
		}

		return array(
			'type'      => $type,
			'product'   => $product,
			'group'     => $group,
			'table'     => 0,
			'overrides' => OverrideCatalog::overrides( $type, $attributes ),
		);
	}

	/**
	 * The attributes a block of this type would lose (the separate migrator's conversion, F10 D16; Task LM2): a name
	 * that is not its target, one of its overrides or the schema panel's, and a toggle with a value it does not keep
	 * (`rating="4.5"`, `price="From $59"`; the block keeps only `hide` or `enable`). A table keeps its ID only.
	 *
	 * @param string                $type       The display's type (read()'s).
	 * @param array<string, string> $attributes The repaired attributes.
	 * @return list<string>
	 */
	public static function lost( string $type, array $attributes ): array {
		$fields = 'table' === $type ? array() : OverrideCatalog::fields( $type );
		$held   = 'table' === $type ? array( 'id', 'type' ) : array_merge( self::TARGET, array_keys( self::SCHEMA ), $fields );
		$lost   = array();
		foreach ( $attributes as $name => $value ) {
			$toggle = in_array( $name, $fields, true ) && 'toggle' === OverrideCatalog::kind( $name );
			if ( ! in_array( $name, $held, true ) || ( $toggle && ! in_array( strtolower( trim( $value ) ), self::TOGGLE_VALUES, true ) ) ) {
				$lost[] = $name;
			}
		}

		return $lost;
	}

	/**
	 * The schema panel's values as the block stores them (Task 24B): `enable` turns a switch on, a price and currency
	 * are kept as typed; nothing for what is off or empty.
	 *
	 * @param array<string, string> $attributes The repaired attributes.
	 * @return array<string, bool|string>
	 */
	public static function schema( array $attributes ): array {
		$block = array();
		foreach ( self::SCHEMA as $name => $attribute ) {
			$value = trim( $attributes[ $name ] ?? '' );
			if ( in_array( $name, array( 'schema_review', 'schema_pros_cons' ), true ) ) {
				if ( 'enable' === strtolower( $value ) ) {
					$block[ $attribute ] = true;
				}
			} elseif ( '' !== $value ) {
				$block[ $attribute ] = $value;
			}
		}

		return $block;
	}
}
