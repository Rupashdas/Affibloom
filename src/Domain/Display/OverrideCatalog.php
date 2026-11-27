<?php
/**
 * Which overrides each display type offers in the editor, and how a value is stored.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Display;

use Affibloom\Domain\Box\BoxOverrides;
use Affibloom\Domain\Shortcode\GroupAttributes;

/**
 * Lasso's Customize Display catalogue (F4 R35-R40; lasso-constant.php LASSO_LINK_CUSTOMIZE_DISPLAY), by shortcode
 * attribute name, in Lasso's order per type. Three toggles are on unless set to `hide` (price, field, rating), two are
 * off unless set to `enable` (data_nosnippet, disclosure); a toggle stores only its non-default value (R39). A value
 * loses its double quotes, `anchor_id` its spaces to `-` (R40); an empty value is no override. The labels and help texts
 * are the Interface's (OverrideTexts), which can translate them.
 */
final class OverrideCatalog {

	/** The fields of each type, in Lasso's order (R36-R38), without Lasso's Brag (decision B9; removed 2026-11-25). */
	private const FIELDS = array(
		'single'  => array( 'title', 'title_url', 'title_type', 'description', 'badge', 'price', 'field', 'rating', 'theme', 'primary_url', 'primary_text', 'secondary_url', 'secondary_text', 'image_url', 'disclosure_text', 'anchor_id' ),
		'button'  => array( 'theme', 'button_type', 'primary_url', 'primary_text', 'anchor_id' ),
		'image'   => array( 'image_url', 'anchor_id' ),
		'grid'    => array( 'columns', 'compact', 'limit', 'title', 'description', 'badge', 'price', 'field', 'rating', 'theme', 'title_type', 'anchor_id', 'disclosure' ),
		'list'    => array( 'bullets', 'description', 'price', 'field', 'rating', 'theme', 'title_type', 'anchor_id' ),
		'gallery' => array( 'columns', 'limit', 'anchor_id' ),
		'table'   => array( 'anchor_id', 'data_nosnippet' ),
	);

	/**
	 * The texts that take an icon in the editors (Task 24G, F28): a single box's badge and buttons, a button's text. A
	 * grid's badge only hides (its help text), so it stays plain.
	 */
	private const ICON_TEXTS = array(
		'single' => array( 'badge', 'primary_text', 'secondary_text' ),
		'button' => array( 'primary_text' ),
	);

	/** Toggles that are on unless the value is `hide`. */
	private const ON_UNLESS_HIDDEN = array( 'price', 'field', 'rating' );

	/**
	 * Toggles that are off until they are turned on, with the value each one then stores. Compact joined them
	 * (2026-11-28, the user: a field whose values are known deserves its own control): it was a line of text whose
	 * help named `hide`, and GroupAttributes reads any of its COMPACT values, so what is stored still renders.
	 */
	private const OFF_TOGGLES = array(
		'data_nosnippet' => 'enable',
		'disclosure'     => 'enable',
		'compact'        => 'hide',
	);

	/**
	 * The fields whose values are a closed list, so the editors offer them instead of a line of text (2026-11-28, the
	 * user). Each list is the one the renderer reads: an empty value is always "the default", which stores no override.
	 */
	private const CHOICES = array(
		'theme'       => Theme::NAMES,
		'title_type'  => BoxOverrides::TITLE_TAGS,
		'columns'     => GroupAttributes::COLUMNS,
		'button_type' => array( 'secondary' ),
		'bullets'     => array( ...ListBullets::ORDERED, ...ListBullets::UNORDERED ),
	);

	/** The fields that hold a web address, so the editors ask for one. */
	private const URLS = array( 'title_url', 'primary_url', 'secondary_url', 'image_url' );

	/** The fields that hold a count. */
	private const NUMBERS = array( 'limit' );

	/**
	 * A type's fields; a block with no type is the single box.
	 *
	 * @param string $type The block's `type`.
	 * @return list<string>
	 */
	public static function fields( string $type ): array {
		return self::FIELDS[ '' === $type ? 'single' : $type ] ?? array();
	}

	/**
	 * Every type with its fields, for the editor.
	 *
	 * @return array<string, list<string>>
	 */
	public static function all(): array {
		return self::FIELDS;
	}

	/**
	 * How the editors show a field: a toggle, a dropdown of the values it takes, a count, an address, the
	 * description's text area, or a line of text.
	 *
	 * @param string $field The shortcode attribute.
	 * @return 'toggle'|'select'|'number'|'url'|'textarea'|'text'
	 */
	public static function kind( string $field ): string {
		if ( in_array( $field, self::ON_UNLESS_HIDDEN, true ) || isset( self::OFF_TOGGLES[ $field ] ) ) {
			return 'toggle';
		}
		if ( isset( self::CHOICES[ $field ] ) ) {
			return 'select';
		}
		if ( in_array( $field, self::NUMBERS, true ) ) {
			return 'number';
		}
		if ( in_array( $field, self::URLS, true ) ) {
			return 'url';
		}

		return 'description' === $field ? 'textarea' : 'text';
	}

	/**
	 * What a field's control offers, in the order the editors show it; the empty default is the editors' own first
	 * entry (OverrideTexts), as it stores no override.
	 *
	 * @param string $field The shortcode attribute.
	 * @return list<string>
	 */
	public static function choices( string $field ): array {
		return self::CHOICES[ $field ] ?? array();
	}

	/**
	 * The value a toggle stores when it is not at its default: `hide` for the three that are on, and for compact;
	 * `enable` for the two that are off. '' for anything that is not a toggle.
	 *
	 * @param string $field The shortcode attribute.
	 */
	public static function stored_value( string $field ): string {
		if ( in_array( $field, self::ON_UNLESS_HIDDEN, true ) ) {
			return 'hide';
		}

		return self::OFF_TOGGLES[ $field ] ?? '';
	}

	/**
	 * Whether a type's field is a badge or button text the editors give an icon picker (Task 24G). Its icon is stored
	 * as `<span class='<class>'></span> <words>`: clean() keeps single quotes, and a shortcode's attribute can hold them.
	 *
	 * @param string $type  The block's `type`; '' is the single box.
	 * @param string $field The shortcode attribute.
	 */
	public static function icon_text( string $type, string $field ): bool {
		return in_array( $field, self::ICON_TEXTS[ '' === $type ? 'single' : $type ] ?? array(), true );
	}

	/**
	 * Whether a toggle is on when the block says nothing (R39).
	 *
	 * @param string $field The toggle's attribute.
	 */
	public static function on_by_default( string $field ): bool {
		return in_array( $field, self::ON_UNLESS_HIDDEN, true );
	}

	/**
	 * A typed value as it is stored (R40): no double quotes, `anchor_id`'s spaces as `-`, the ends trimmed; the
	 * description keeps its line breaks.
	 *
	 * @param string $field The attribute.
	 * @param string $value What was typed.
	 */
	public static function clean( string $field, string $value ): string {
		$value = trim( str_replace( '"', '', $value ) );

		return 'anchor_id' === $field ? (string) preg_replace( '/\s+/', '-', $value ) : $value;
	}

	/**
	 * A toggle's stored value: its own, or '' for its default. Compact keeps whatever Lasso's shortcode could say
	 * (GroupAttributes::COMPACT) by storing `hide` for any of them, so a converted box keeps its compact cards.
	 *
	 * @param string $field The toggle's attribute.
	 * @param string $value What is stored, lower case.
	 */
	private static function toggle_value( string $field, string $value ): string {
		$stored = self::stored_value( $field );
		if ( 'compact' === $field ) {
			return in_array( $value, GroupAttributes::COMPACT, true ) ? $stored : '';
		}

		return $value === $stored ? $stored : '';
	}

	/**
	 * A block's stored overrides as the shortcode reads them: the type's fields only, cleaned, empty ones dropped, and a
	 * toggle only at its non-default value (`hide` or `enable`).
	 *
	 * @param string               $type   The block's `type`.
	 * @param array<string, mixed> $values The block's `overrides`.
	 * @return array<string, string>
	 */
	public static function overrides( string $type, array $values ): array {
		$kept = array();
		foreach ( self::fields( $type ) as $field ) {
			$value = $values[ $field ] ?? '';
			if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
				continue;
			}
			$value = self::clean( $field, (string) $value );
			if ( 'toggle' === self::kind( $field ) ) {
				$value = self::toggle_value( $field, strtolower( $value ) );
			}
			if ( '' !== $value ) {
				$kept[ $field ] = $value;
			}
		}

		return $kept;
	}
}
